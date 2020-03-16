<?php declare(strict_types=1);

namespace Codedge\Updater\SourceRepositoryTypes;

use Codedge\Updater\Models\Release;
use Codedge\Updater\SourceRepositoryTypes\GithubRepositoryTypes\GithubTagType;
use Codedge\Updater\Tests\TestCase;
use Exception;
use InvalidArgumentException;

class HttpRepositoryTypeTest extends TestCase
{
    /** @test */
    public function it_can_instantiate(): void
    {
        $this->assertInstanceOf(HttpRepositoryType::class, resolve(HttpRepositoryType::class));
    }

    /** @test */
    public function it_can_run_update(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $release = resolve(Release::class);
        $release->setStoragePath('/tmp')
                ->setRelease('release-1.0.zip')
                ->updateStoragePath()
                ->setDownloadUrl('some-local-file')
                ->download($this->getMockedDownloadZipFileClient());
        $release->extract();

        $this->assertTrue($http->update($release));
    }

    /** @test */
    public function it_cannot_fetch_http_releases_if_no_url_specified(): void
    {
        config(['self-update.repository_types.http.repository_url' => '']);

        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $this->expectException(Exception::class);
        $http->fetch();
    }

    /** @test */
    public function it_cannot_fetch_releases_because_there_is_no_release(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $this->assertInstanceOf(Release::class, $http->fetch());

        $this->expectException(Exception::class);
        $this->assertInstanceOf(Release::class, $http->fetch());
    }

    /** @test */
    public function it_cannot_fetch_releases_because_there_is_no_release_with_access_token(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);
        $http->setAccessToken('123');

        $this->assertInstanceOf(Release::class, $http->fetch());

        $this->expectException(Exception::class);
        $this->assertInstanceOf(Release::class, $http->fetch());
    }


    public function it_can_fetch_http_releases(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $this->assertInstanceOf(Release::class, $http->fetch());

        // Fetch again when source is already fetched
        $this->assertInstanceOf(Release::class, $http->fetch());
    }

    /** @test */
    public function it_can_get_the_version_installed(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);
        $this->assertEmpty($http->getVersionInstalled());

        config(['self-update.version_installed' => '1.0']);
        $this->assertEquals('1.0', $http->getVersionInstalled());
    }

    /** @test */
    public function it_can_get_latest_release_from_collection():void
    {
        $items = [
            [ 'name' => '1.3', ],
            [ 'name' => '1.2', ],
        ];

        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $this->assertEquals($items[0], $http->selectRelease(collect($items), ''));
    }

    /** @test */
    public function it_can_get_specific_release_from_collection(): void
    {
        $items = [
            [ 'name' => '1.3', ],
            [ 'name' => '1.2', ],
        ];

        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $this->assertEquals($items[1], $http->selectRelease(collect($items), '1.2'));
    }

    /** @test */
    public function it_cannot_find_specific_release_and_returns_first_from_collection(): void
    {
        $items = [
            [ 'name' => '1.3', ],
            [ 'name' => '1.2', ],
        ];

        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $this->assertEquals($items[0], $http->selectRelease(collect($items), '1.4'));
    }

    /** @test */
    public function it_cannot_get_new_version_available_and_fails_with_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var HttpRepositoryType $github */
        $http = resolve(HttpRepositoryType::class);
        $http->isNewVersionAvailable('');
    }

    /** @test */
    public function it_can_get_new_version_available_from_type_tag_without_version_file(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);
        $http->deleteVersionFile();

        $this->assertTrue($http->isNewVersionAvailable('4.5'));
        $this->assertFalse($http->isNewVersionAvailable('5.0'));
    }

    /** @test */
    public function it_can_handle_access_tokens(): void
    {
        /** @var HttpRepositoryType $http */
        $http = resolve(HttpRepositoryType::class);

        $http->setAccessTokenPrefix('Tester ');
        $http->setAccessToken('123');

        $this->assertEquals('Tester 123', $http->getAccessToken());
        $this->assertTrue($http->hasAccessToken());
        $this->assertEquals('Tester ', $http->getAccessTokenPrefix());
        $this->assertEquals('123', $http->getAccessToken(false));
    }
}
