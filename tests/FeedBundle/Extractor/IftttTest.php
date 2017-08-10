<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Ifttt;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class IftttTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://ifttt.com/recipes/385105-receive-notifications-for-a-jailbreak', true],
            ['http://ifttt.com/recipes/385105-receive-notifications-for-a-jailbreak', true],
            ['https://ifttt.com/recipes/385105', true],
            ['https://ifttt.com', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $ifttt = new Ifttt();
        $this->assertSame($expected, $ifttt->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['title' => 'my title', 'description' => 'Cool stuff bro']))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $ifttt = new Ifttt();
        $ifttt->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $ifttt->setLogger($logger);

        // first test fail because we didn't match an url, so IftttUrl isn't defined
        $this->assertEmpty($ifttt->getContent());

        $ifttt->match('https://ifttt.com/recipes/385105-receive-notifications-for-a-jailbreak');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p>Cool stuff bro</p><p><a href="https://ifttt.com/recipes/385105"><img src="https://ifttt.com/recipe_embed_img/385105"></a></p></div>', $ifttt->getContent());
        // this one will got an empty array
        $this->assertEmpty($ifttt->getContent());
        // this one will catch an exception
        $this->assertEmpty($ifttt->getContent());

        $this->assertTrue($logHandler->hasWarning('Ifttt extract failed for: 385105'), 'Warning message matched');
    }
}
