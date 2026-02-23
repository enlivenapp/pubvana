<?php

use App\Models\CommentModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class CommentModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private CommentModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new CommentModel();
    }

    // approved() / pending() scopes -----------------------------------------

    public function testApprovedScopeReturnsOnlyApproved(): void
    {
        $comments = $this->model->approved()->findAll();
        $this->assertNotEmpty($comments);
        foreach ($comments as $c) {
            $this->assertSame('approved', $c->status);
        }
    }

    public function testPendingScopeReturnsOnlyPending(): void
    {
        $comments = $this->model->pending()->findAll();
        $this->assertNotEmpty($comments);
        foreach ($comments as $c) {
            $this->assertSame('pending', $c->status);
        }
    }

    // getTree() --------------------------------------------------------------

    public function testGetTreeReturnsArrayForPost(): void
    {
        $tree = $this->model->getTree(1);
        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
    }

    public function testGetTreeExcludesPendingComments(): void
    {
        $tree = $this->model->getTree(1);
        // Flatten tree and check all statuses
        $flat = $this->flattenTree($tree);
        foreach ($flat as $comment) {
            $this->assertNotSame('pending', $comment->status);
        }
    }

    public function testGetTreeHasChildComments(): void
    {
        $tree = $this->model->getTree(1);
        $this->assertNotEmpty($tree);
        // Comment 1 should have comment 2 as child
        $root = $tree[0];
        $this->assertIsArray($root->children);
        $this->assertNotEmpty($root->children);
    }

    public function testGetTreeMaxDepthLimitsNesting(): void
    {
        // With maxDepth=1, no children should have children
        $tree = $this->model->getTree(1, 1);
        foreach ($tree as $root) {
            $this->assertEmpty($root->children);
        }
    }

    public function testGetTreeEmptyForPostWithNoComments(): void
    {
        $tree = $this->model->getTree(999);
        $this->assertIsArray($tree);
        $this->assertEmpty($tree);
    }

    // insert / CRUD ----------------------------------------------------------

    public function testInsertCommentAndRetrieve(): void
    {
        $id = $this->model->insert([
            'post_id'      => 1,
            'author_name'  => 'TestUser',
            'author_email' => 'test@example.com',
            'content'      => 'Test comment content',
            'status'       => 'approved',
            'parent_id'    => null,
        ]);
        $this->assertIsInt($id);

        $comment = $this->model->find($id);
        $this->assertNotNull($comment);
        $this->assertSame('Test comment content', $comment->content);
    }

    // Helpers ----------------------------------------------------------------

    private function flattenTree(array $tree): array
    {
        $flat = [];
        foreach ($tree as $node) {
            $flat[] = $node;
            if (! empty($node->children)) {
                $flat = array_merge($flat, $this->flattenTree($node->children));
            }
        }
        return $flat;
    }
}
