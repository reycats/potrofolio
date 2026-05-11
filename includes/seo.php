<?php
/**
 * SEO Generator untuk XT4 Blog.
 *
 * Menghasilkan tag HTML untuk Open Graph, Twitter Cards, canonical,
 * dan JSON-LD structured data (Person / Article) berdasarkan data
 * yang diberikan.
 *
 * @package XT4
 */
class SEO {
    private string $siteName = 'XT4';
    private string $baseUrl;
    private string $defaultImage;
    private string $twitterHandle = '@xt4_official';

    public function __construct() {
        $this->baseUrl = rtrim(env('APP_URL', 'https://xt4.my.id'), '/');
        $this->defaultImage = $this->baseUrl . '/assets/images/xt4-og.jpg';
    }

    /**
     * Render seluruh meta tags.
     *
     * @param array $page [
     *   'title' => string,
     *   'description' => string,
     *   'type' => 'website'|'article',
     *   'url' => string (relative or absolute),
     *   'image' => string (optional),
     *   'published_time' => string (ISO 8601),
     *   'updated_time' => string,
     *   'author' => string,
     *   'tags' => array
     * ]
     * @return string HTML
     */
    public function renderMeta(array $page = []): string {
        $title       = e($page['title'] ?? $this->siteName . ' - Personal Branding & Portfolio');
        $description = e($page['description'] ?? 'XT4 adalah brand personal fokus pada teknologi, desain, dan kreativitas masa depan.');
        $type        = $page['type'] ?? 'website';
        $url         = $this->baseUrl . ($page['url'] ?? '/');
        $image       = $page['image'] ?? $this->defaultImage;

        $meta = '';
        $meta .= "<title>{$title}</title>\n";
        $meta .= "<meta name=\"description\" content=\"{$description}\">\n";
        $meta .= "<meta name=\"author\" content=\"XT4\">\n";
        $meta .= "<link rel=\"canonical\" href=\"{$url}\">\n";

        // Open Graph
        $meta .= "<meta property=\"og:site_name\" content=\"{$this->siteName}\">\n";
        $meta .= "<meta property=\"og:title\" content=\"{$title}\">\n";
        $meta .= "<meta property=\"og:description\" content=\"{$description}\">\n";
        $meta .= "<meta property=\"og:type\" content=\"{$type}\">\n";
        $meta .= "<meta property=\"og:url\" content=\"{$url}\">\n";
        $meta .= "<meta property=\"og:image\" content=\"{$image}\">\n";
        $meta .= "<meta property=\"og:locale\" content=\"id_ID\">\n";

        // Twitter
        $meta .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        $meta .= "<meta name=\"twitter:site\" content=\"{$this->twitterHandle}\">\n";
        $meta .= "<meta name=\"twitter:title\" content=\"{$title}\">\n";
        $meta .= "<meta name=\"twitter:description\" content=\"{$description}\">\n";
        $meta .= "<meta name=\"twitter:image\" content=\"{$image}\">\n";

        return $meta;
    }

    /**
     * Generate JSON-LD untuk Person (XT4).
     */
    public function jsonLdPerson(): string {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            'name'     => 'XT4',
            'url'      => $this->baseUrl,
            'sameAs'   => [
                'https://twitter.com/xt4_official',
                'https://github.com/xt4',
                'https://instagram.com/xt4',
            ],
            'jobTitle' => 'Digital Creator & Developer',
            'image'    => $this->defaultImage
        ];
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * JSON-LD Article untuk halaman blog post.
     */
    public function jsonLdArticle(array $post): string {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Article',
            'headline' => $post['title'],
            'description' => $post['description'] ?? '',
            'image'   => $post['image_url'] ?? $this->defaultImage,
            'author'  => [
                '@type' => 'Person',
                'name'  => $post['author'] ?? 'XT4',
                'url'   => $this->baseUrl
            ],
            'datePublished' => $post['created_at'],
            'dateModified'  => $post['updated_at'] ?? $post['created_at'],
            'mainEntityOfPage' => $this->baseUrl . '/post/' . ($post['slug'] ?? ''),
        ];
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
