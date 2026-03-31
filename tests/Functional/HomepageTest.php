<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class HomepageTest extends WebTestCase
{
    public function testHomepageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSame('Foedex', $crawler->filter('title')->text());
        self::assertCount(1, $crawler->filter('#root'));
        self::assertStringStartsWith('/app/app.js?v=', (string) $crawler->filter('script[type="module"]')->attr('src'));
    }
}
