<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Trust;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Trust\Services\TrustService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

#[CoversClass(TrustService::class)]
final class TrustServiceTest extends TestCase
{
    private \PDO $pdo;
    private TrustService $service;

    protected function setUp(): void
    {
        $this->pdo = Sqlite::recreate();

        $app = $this->app([
            'view' => fn () => new class {
                /** @param array<string, mixed> $data */
                public function fetch(string $view, array $data = []): string
                {
                    return '<html></html>';
                }
            },
            'mailer' => fn () => new class {
                /** @param array<string, mixed> $opts */
                public function sendHtml(string $to, string $subject, string $body, array $opts = []): void
                {
                    throw new \RuntimeException('no mail in tests');
                }
            },
        ]);

        $this->service = new TrustService($this->pdo, $app);
    }

    public function testNormalizeAuthor(): void
    {
        self::assertSame('awesome_plugin_dev', $this->service->normalizeAuthor('Awesome Plugin Dev'));
        self::assertSame('acme', $this->service->normalizeAuthor('  Acme  '));
    }

    // -----------------------------------------------------------------
    // Check API validation
    // -----------------------------------------------------------------

    public function testApiCheckRejectsMissingFields(): void
    {
        $result = $this->service->apiCheck([]);

        self::assertSame(false, $result['valid']);
        self::assertStringContainsString('Missing required fields', (string) ($result['error'] ?? ''));
    }

    public function testApiCheckRejectsEmptyItems(): void
    {
        $result = $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://example.com',
            'items'      => [],
        ]);

        self::assertSame(false, $result['valid']);
    }

    public function testApiCheckRejectsItemMissingFields(): void
    {
        $result = $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://example.com',
            'items'      => [['type' => 'plugin', 'slug' => 'foo', 'version' => '0.1.0']],
        ]);

        self::assertSame(false, $result['valid']);
    }

    public function testApiCheckReportsUnknownForUnlistedAddon(): void
    {
        $result = $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://example.com',
            'items'      => [
                ['type' => 'plugin', 'slug' => 'acme-addon', 'version' => '0.1.0', 'author' => 'acme'],
            ],
        ]);

        self::assertSame(true, $result['valid']);
        self::assertSame('unknown', ($result['results'] ?? [])[0]['status']);
        self::assertNull(($result['results'] ?? [])[0]['warning']);
    }

    public function testApiCheckMatchesAddonsRow(): void
    {
        $this->service->addonsCreate([
            'type'    => 'plugin',
            'slug'    => 'acme-addon',
            'author'  => 'acme',
            'status'  => 'trusted',
            'warning' => '',
        ]);

        $result = $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://example.com',
            'items'      => [
                ['type' => 'plugin', 'slug' => 'acme-addon', 'version' => '0.1.0', 'author' => 'acme'],
            ],
        ]);

        self::assertSame(true, $result['valid']);
        self::assertSame('trusted', ($result['results'] ?? [])[0]['status']);
    }

    public function testApiCheckInheritsLineStanding(): void
    {
        $this->service->addonsCreate([
            'type'    => 'plugin',
            'slug'    => 'legacy-addon',
            'author'  => 'acme',
            'status'  => 'known',
            'warning' => 'Outdated API usage',
        ]);

        $result = $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://example.com',
            'items'      => [
                ['type' => 'plugin', 'slug' => 'legacy-addon', 'version' => '9.9.9', 'author' => 'acme'],
            ],
        ]);

        self::assertSame('known', ($result['results'] ?? [])[0]['status']);
        self::assertSame('Outdated API usage', ($result['results'] ?? [])[0]['warning']);
    }

    public function testApiCheckExactPinnedReleaseWins(): void
    {
        // Known line by default; 1.0.0 was vetted clean on its own.
        $addon = $this->service->addonsCreate([
            'type'    => 'plugin',
            'slug'    => 'pinned-addon',
            'author'  => 'acme',
            'status'  => 'known',
            'warning' => null,
        ]);
        $this->service->addonVersionCreate([
            'addon_id' => (int) $addon->id,
            'version'  => '1.0.0',
            'status'   => 'trusted',
            'warning'  => '',
        ]);

        $result = $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://example.com',
            'items'      => [
                ['type' => 'plugin', 'slug' => 'pinned-addon', 'version' => '1.0.0', 'author' => 'acme'],
                ['type' => 'plugin', 'slug' => 'pinned-addon', 'version' => '1.0.1', 'author' => 'acme'],
            ],
        ]);

        $results = $result['results'] ?? [];
        self::assertSame('trusted', $results[0]['status']);
        self::assertSame('known', $results[1]['status']);
    }

    public function testApiCheckRecordsCheckIn(): void
    {
        $this->service->apiCheck([
            'pv_version' => '3.0.0',
            'base_url'   => 'https://calling.example.com',
            'items'      => [
                ['type' => 'plugin', 'slug' => 'x', 'version' => '0.1.0', 'author' => 'a'],
            ],
        ]);

        $rows = $this->fetchRows(
            'SELECT * FROM trust_instances WHERE base_url = :base',
            [':base' => 'https://calling.example.com']
        );
        self::assertSame('3.0.0', $rows[0]['pv_version'] ?? null);
    }

    // -----------------------------------------------------------------
    // Instance check-ins
    // -----------------------------------------------------------------

    public function testCheckInUpsertsByBaseUrl(): void
    {
        $this->service->checkIn('https://site.example.com', '3.0.0');
        $this->service->checkIn('https://site.example.com', '3.1.0');

        $rows = $this->fetchRows('SELECT * FROM trust_instances');
        self::assertCount(1, $rows);
        self::assertSame('3.1.0', $rows[0]['pv_version'] ?? null);
    }

    public function testInstancesCount(): void
    {
        $this->service->checkIn('https://a.example.com', '3.0.0');
        $this->service->checkIn('https://b.example.com', '3.0.0');

        self::assertSame(2, $this->service->instancesCount());
    }

    // -----------------------------------------------------------------
    // Requests flow
    // -----------------------------------------------------------------

    public function testRequestCreateStartsPending(): void
    {
        $request = $this->service->requestCreate([
            'type'         => 'plugin',
            'slug'         => 'nice-addon',
            'version'      => '2.1.0',
            'author'       => 'Bob Builder',
            'author_email' => 'bob@example.com',
            'zip_path'     => '/tmp/nice.zip',
        ]);

        self::assertGreaterThan(0, (int) $request->id);
        self::assertSame('pending', (string) $request->status);
        self::assertSame('bob_builder', (string) $request->author);
    }

    public function testRequestsByEmailFilters(): void
    {
        $this->service->requestCreate([
            'type'         => 'theme',
            'slug'         => 'shiny',
            'version'      => '0.1.0',
            'author'       => 'Bob',
            'author_email' => 'bob@example.com',
            'zip_path'     => '/tmp/x.zip',
        ]);
        $this->service->requestCreate([
            'type'         => 'theme',
            'slug'         => 'dull',
            'version'      => '0.1.0',
            'author'       => 'Alice',
            'author_email' => 'alice@example.com',
            'zip_path'     => '/tmp/y.zip',
        ]);

        $results = $this->service->requestsByEmail('bob@example.com');

        self::assertCount(1, $results);
        self::assertSame('shiny', (string) $results[0]->slug);
    }

    public function testSetReviewingMarksStatus(): void
    {
        $request = $this->service->requestCreate([
            'type'         => 'plugin',
            'slug'         => 'nice-addon',
            'version'      => '2.1.0',
            'author'       => 'Bob',
            'author_email' => 'bob@example.com',
            'zip_path'     => '/tmp/nice.zip',
        ]);

        $updated = $this->service->setReviewing((int) $request->id);

        self::assertNotNull($updated);
        self::assertSame('reviewing', (string) $updated->status);
    }

    public function testDecideCreatesAddonEntryAndDeletesZip(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'trust') . '.zip';
        file_put_contents($zipPath, 'zip data');

        $request = $this->service->requestCreate([
            'type'         => 'plugin',
            'slug'         => 'nice-addon',
            'version'      => '2.1.0',
            'author'       => 'Bob',
            'author_email' => 'bob@example.com',
            'zip_path'     => $zipPath,
        ]);

        $ok = $this->service->decide((int) $request->id, 'trusted', 'Welcome aboard.', '');

        self::assertTrue($ok);
        self::assertFileDoesNotExist($zipPath);

        $line = $this->addonsLineRow('plugin', 'nice-addon', 'bob');
        self::assertSame('trusted', $line['status'] ?? null);

        $pin = $this->versionPinRow('plugin', 'nice-addon', '2.1.0', 'bob');
        self::assertSame('trusted', $pin['status'] ?? null);

        $rows = $this->fetchRows(
            'SELECT status, decision_note FROM trust_requests WHERE id = :id',
            [':id' => (int) $request->id]
        );
        self::assertSame('trusted', $rows[0]['status'] ?? null);
        self::assertSame('Welcome aboard.', $rows[0]['decision_note'] ?? null);
    }

    public function testDecideKnownRequiresWarningStored(): void
    {
        $request = $this->service->requestCreate([
            'type'         => 'plugin',
            'slug'         => 'sketchy-addon',
            'version'      => '0.1.0',
            'author'       => 'Bob',
            'author_email' => 'bob@example.com',
            'zip_path'     => '/tmp/sketchy.zip',
        ]);

        $this->service->decide((int) $request->id, 'known', '', 'Uses deprecated hooks');

        $line = $this->addonsLineRow('plugin', 'sketchy-addon', 'bob');
        self::assertSame('known', $line['status'] ?? null);
        self::assertSame('Uses deprecated hooks', $line['warning'] ?? null);

        $pin = $this->versionPinRow('plugin', 'sketchy-addon', '0.1.0', 'bob');
        self::assertSame('known', $pin['status'] ?? null);
    }

    public function testSecondDecisionPinsReleaseAndKeepsLine(): void
    {
        // An already-vetted line keeps its own standing; the later decision
        // of another release only files a pin under the line.
        $this->service->addonsCreate([
            'type'    => 'plugin',
            'slug'    => 'steady-addon',
            'author'  => 'acme',
            'status'  => 'trusted',
            'warning' => '',
        ]);
        $request = $this->service->requestCreate([
            'type'         => 'plugin',
            'slug'         => 'steady-addon',
            'version'      => '3.0.0',
            'author'       => 'Acme',
            'author_email' => 'acme@example.com',
            'zip_path'     => '/tmp/steady.zip',
        ]);

        $this->service->decide((int) $request->id, 'known', '', 'Needs an eye');

        $line = $this->addonsLineRow('plugin', 'steady-addon', 'acme');
        self::assertSame('trusted', $line['status'] ?? null);

        $pin = $this->versionPinRow('plugin', 'steady-addon', '3.0.0', 'acme');
        self::assertSame('known', $pin['status'] ?? null);
        self::assertSame('Needs an eye', $pin['warning'] ?? null);
    }

    public function testRejectDeletesRequest(): void
    {
        $request = $this->service->requestCreate([
            'type'         => 'theme',
            'slug'         => 'meh',
            'version'      => '0.1.0',
            'author'       => 'Alice',
            'author_email' => 'alice@example.com',
            'zip_path'     => '/tmp/meh.zip',
        ]);

        self::assertTrue($this->service->reject((int) $request->id, 'Missing documentation.'));

        self::assertSame(
            [],
            $this->fetchRows('SELECT * FROM trust_requests WHERE id = :id', [':id' => (int) $request->id])
        );
    }

    public function testReleasePinnedDedupe(): void
    {
        $request = $this->service->requestCreate([
            'type'         => 'plugin',
            'slug'         => 'resubmitted',
            'version'      => '1.0.0',
            'author'       => 'Bob',
            'author_email' => 'bob@example.com',
            'zip_path'     => '/tmp/a.zip',
        ]);
        $this->service->decide((int) $request->id, 'trusted', '', '');

        self::assertTrue($this->service->releasePinned('plugin', 'resubmitted', '1.0.0', 'bob'));
        self::assertFalse($this->service->releasePinned('plugin', 'resubmitted', '2.0.0', 'bob'));
        self::assertFalse($this->service->releasePinned('plugin', 'unknown', '1.0.0', 'bob'));
    }

    public function testAddonsStats(): void
    {
        $this->service->addonsCreate(['type' => 'plugin', 'slug' => 'a', 'author' => 'a', 'status' => 'trusted', 'warning' => '']);
        $this->service->addonsCreate(['type' => 'plugin', 'slug' => 'b', 'author' => 'b', 'status' => 'known', 'warning' => 'x']);
        $this->service->addonsCreate(['type' => 'plugin', 'slug' => 'c', 'author' => 'c', 'status' => 'malicious', 'warning' => 'y']);

        $stats = $this->service->addonsStats();

        self::assertSame(1, $stats['trusted']);
        self::assertSame(1, $stats['known']);
        self::assertSame(1, $stats['malicious']);
        self::assertSame(3, $stats['total']);
    }

    public function testVersionCrudRoundTrip(): void
    {
        $addon = $this->service->addonsCreate([
            'type'    => 'theme',
            'slug'    => 'theme-under-test',
            'author'  => 'acme',
            'status'  => 'known',
            'warning' => '',
        ]);

        // Pin, then refresh the same release (upsert path answers the pin).
        $pin = $this->service->addonVersionCreate([
            'addon_id' => (int) $addon->id,
            'version'  => '2.0.0',
            'status'   => 'malicious',
            'warning'  => 'Bad',
        ]);
        $same = $this->service->addonVersionCreate([
            'addon_id' => (int) $addon->id,
            'version'  => '2.0.0',
            'status'   => 'known',
            'warning'  => 'Softened',
        ]);

        self::assertSame((int) $pin->id, (int) $same->id);
        self::assertSame('known', (string) $same->status);
        self::assertSame('Softened', $same->warning);

        $updated = $this->service->addonVersionUpdate((int) $pin->id, ['status' => 'trusted']);
        self::assertNotNull($updated);
        self::assertSame('trusted', (string) $updated->status);

        self::assertTrue($this->service->addonVersionDelete((int) $pin->id));
        self::assertFalse($this->service->addonVersionDelete((int) $pin->id));
    }

    /**
     * @return array<string, mixed> The vetted line row, or [] when absent.
     */
    private function addonsLineRow(string $type, string $slug, string $author): array
    {
        return $this->fetchRows(
            'SELECT * FROM trust_addons WHERE type = :type AND slug = :slug AND author = :author',
            [
                ':type'   => $type,
                ':slug'   => $slug,
                ':author' => $author,
            ]
        )[0] ?? [];
    }

    /**
     * @return array<string, mixed> The pinned release row, or [] when absent.
     */
    private function versionPinRow(string $type, string $slug, string $version, string $author): array
    {
        $rows = $this->fetchRows(
            'SELECT rv.* FROM trust_addon_versions rv
             INNER JOIN trust_addons ra ON ra.id = rv.addon_id
             WHERE ra.type = :type AND ra.slug = :slug AND ra.author = :author AND rv.version = :version',
            [
                ':type'    => $type,
                ':slug'    => $slug,
                ':version' => $version,
                ':author'  => $author,
            ]
        );
        return $rows[0] ?? [];
    }

    /**
     * Run a parameterized query and fetch all rows.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }
}