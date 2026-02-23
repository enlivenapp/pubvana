<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for functions in app/Helpers/cms_helper.php
 *
 * @internal
 */
final class CmsHelperTest extends CIUnitTestCase
{
    // slug_from_title --------------------------------------------------------

    public function testSlugFromTitleBasic(): void
    {
        $this->assertSame('hello-world', slug_from_title('Hello World'));
    }

    public function testSlugFromTitleUppercase(): void
    {
        $this->assertSame('codeigniter-4', slug_from_title('CodeIgniter 4'));
    }

    public function testSlugFromTitleSpecialChars(): void
    {
        $slug = slug_from_title('Foo & Bar: Baz!');
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $slug);
    }

    public function testSlugFromTitleEmpty(): void
    {
        $this->assertSame('', slug_from_title(''));
    }

    // post_url ---------------------------------------------------------------

    public function testPostUrlReturnsCorrectUrl(): void
    {
        $url = post_url('my-post');
        $this->assertStringContainsString('blog/my-post', $url);
    }

    public function testPostUrlIsString(): void
    {
        $this->assertIsString(post_url('test'));
    }

    // category_url -----------------------------------------------------------

    public function testCategoryUrlReturnsCorrectUrl(): void
    {
        $url = category_url('news');
        $this->assertStringContainsString('category/news', $url);
    }

    // tag_url ----------------------------------------------------------------

    public function testTagUrlReturnsCorrectUrl(): void
    {
        $url = tag_url('php');
        $this->assertStringContainsString('tag/php', $url);
    }

    // excerpt ----------------------------------------------------------------

    public function testExcerptShortTextUnchanged(): void
    {
        $text = 'Short text.';
        $this->assertSame('Short text.', excerpt($text, 150));
    }

    public function testExcerptTruncatesLongText(): void
    {
        $text   = str_repeat('a', 200);
        $result = excerpt($text, 150);
        $this->assertLessThanOrEqual(155, strlen($result)); // 150 + ellipsis char
        $this->assertStringEndsWith('…', $result);
    }

    public function testExcerptStripsHtmlTags(): void
    {
        $html   = '<p>Hello <strong>World</strong></p>';
        $result = excerpt($html, 150);
        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    public function testExcerptCustomLength(): void
    {
        $text   = 'The quick brown fox jumps over the lazy dog';
        $result = excerpt($text, 10);
        $this->assertStringEndsWith('…', $result);
    }

    public function testExcerptExactLengthNotTruncated(): void
    {
        $text   = str_repeat('x', 150);
        $result = excerpt($text, 150);
        $this->assertSame($text, $result);
    }
}
