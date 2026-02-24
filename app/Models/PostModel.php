<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table         = 'posts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'title', 'slug', 'content', 'content_type', 'excerpt', 'status',
        'featured_image', 'author_id', 'published_at', 'views', 'is_featured', 'is_premium',
        'meta_title', 'meta_description', 'lang', 'share_on_publish', 'preview_token',
    ];

    public function published(): static
    {
        return $this->where('status', 'published')->where('published_at <=', date('Y-m-d H:i:s'));
    }

    public function featured(): static
    {
        return $this->where('is_featured', 1);
    }

    public function byCategory(int $id): static
    {
        return $this->join('posts_to_categories ptc', 'ptc.post_id = posts.id')
                    ->where('ptc.category_id', $id);
    }

    public function byTag(int $id): static
    {
        return $this->join('tags_to_posts ttp', 'ttp.post_id = posts.id')
                    ->where('ttp.tag_id', $id);
    }

    public function byAuthor(int $id): static
    {
        return $this->where('author_id', $id);
    }

    public function incrementViews(int $id): void
    {
        $this->set('views', 'views + 1', false)->where('id', $id)->update();
    }

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    public function generateToken(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update($id, ['preview_token' => $token]);
        return $token;
    }

    public function findByPreviewToken(string $token): ?object
    {
        return $this->where('preview_token', $token)->first();
    }
}
