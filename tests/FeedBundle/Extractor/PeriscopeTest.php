<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Periscope;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class PeriscopeTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://www.pscp.tv/w/1ynJOVNmoVkGR', true],
            ['http://www.pscp.tv/w/1ynJOVNmoVkGR', true],
            ['http://pscp.tv/w/1ynJOVNmoVkGR', true],
            ['https://www.periscope.tv/NASA/1ynJOVNmoVkGR', true],
            ['http://www.periscope.tv/NASA/1ynJOVNmoVkGR', true],
            ['http://periscope.tv/NASA/1ynJOVNmoVkGR', true],
            ['http://periscope.tv/NASA/dsq', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $vimeo = new Periscope();
        $this->assertSame($expected, $vimeo->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['broadcast' => ['status' => 'my title', 'image_url' => 'http://0.0.0.0/img.jpg'], 'share_url' => 'http://broadcast.url']))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $vimeo = new Periscope();
        $vimeo->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $vimeo->setLogger($logger);

        // first test fail because we didn't match an url, so PeriscopeUrl isn't defined
        $this->assertEmpty($vimeo->getContent());

        $vimeo->match('https://www.pscp.tv/w/1ynJOVNmoVkGR');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p>Broadcast available on <a href="http://broadcast.url">Periscope</a>.</p><p><img src="http://0.0.0.0/img.jpg"></p></div>', $vimeo->getContent());
        // this one will got an empty array
        $this->assertEmpty($vimeo->getContent());
        // this one will catch an exception
        $this->assertEmpty($vimeo->getContent());

        $this->assertTrue($logHandler->hasWarning('Periscope extract failed for: 1ynJOVNmoVkGR'), 'Warning message matched');
    }
}
