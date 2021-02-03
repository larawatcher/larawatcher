<?php

namespace Larawatcher\Tests;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Larawatcher\Actions\Save;
use Larawatcher\Larawatcher;
use Mockery;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LarawatcherTest extends TestCase
{
    /** @test */
    public function it_set_the_process_type_to_route()
    {
        $route = new Route('get', 'test', []);
        $request = new Request();
        $route->bind($request);

        $event = new RouteMatched($route, $request);

        event($event);

        $laraWatcher = resolve(Larawatcher::class);

        $this->assertEquals('route', $laraWatcher->getType());
        $this->assertEquals('test', $laraWatcher->getAttribute());
    }

    /** @test */
    public function it_set_the_process_type_to_command()
    {
        $event = new CommandStarting(
            'test',
            Mockery::mock(InputInterface::class),
            Mockery::mock(OutputInterface::class),
        );

        event($event);

        $laraWatcher = resolve(Larawatcher::class);

        $this->assertEquals('command', $laraWatcher->getType());
        $this->assertEquals('test', $laraWatcher->getAttribute());
    }

    /** @test */
    public function it_set_the_process_type_to_job()
    {
        $mockJob = Mockery::mock(Job::class)
            ->shouldReceive('resolveName')
            ->andReturn('test')
            ->getMock();

        $event = new JobProcessing('sync', $mockJob);

        event($event);

        $laraWatcher = resolve(Larawatcher::class);

        $this->assertEquals('job', $laraWatcher->getType());
        $this->assertEquals('test', $laraWatcher->getAttribute());
    }

    /** @test */
    public function it_should_not_change_the_type_if_the_type_is_already_set()
    {
        $route = new Route('get', 'test', []);
        $request = new Request();
        $route->bind($request);

        event(new RouteMatched($route, $request));
        event(new CommandStarting('test', Mockery::mock(InputInterface::class), Mockery::mock(OutputInterface::class)));

        $laraWatcher = resolve(Larawatcher::class);

        $this->assertEquals('route', $laraWatcher->getType());
        $this->assertEquals('test', $laraWatcher->getAttribute());
    }

    /** @test */
    public function it_pushes_the_queries_to_the_array()
    {
        $databaseConnection = $this->getDbConnectionMock();
        $queryOne = new QueryExecuted('sql1', ['name' => 'test1'], 1, $databaseConnection);
        $queryTwo = new QueryExecuted('sql2', ['name' => 'test2'], 2, $databaseConnection);
        event($queryOne);
        event($queryTwo);

        $queries = resolve(Larawatcher::class)->getQueries();

        $this->assertCount(2, $queries);
        $this->assertEquals($queries[0]->getQueryExecuted(), $queryOne);
        $this->assertEquals($queries[1]->getQueryExecuted(), $queryTwo);
    }

    /** @test */
    public function it_pushes_the_queries_within_tag_fragments()
    {
        app()['config']->set('larawatcher.tags_only', true);

        $databaseConnection = $this->getDbConnectionMock();

        lw_tag('test');
        $queryOne = new QueryExecuted('sql1', ['name' => 'test1'], 1, $databaseConnection);
        event($queryOne);
        lw_untag('test');

        $queryTwo = new QueryExecuted('sql2', ['name' => 'test2'], 2, $databaseConnection);
        event($queryTwo);

        $queries = resolve(Larawatcher::class)->getQueries();
        $this->assertCount(1, $queries);
        $this->assertEquals($queries[0]->getQueryExecuted(), $queryOne);
    }

    /** @test */
    public function it_pushes_hydrated_models_to_the_array()
    {
        event('eloquent.retrieved:*', [new class extends Model {
        }]);

        $models = resolve(Larawatcher::class)->getHydratedModels();

        $this->assertEquals(1, $models);
    }

    /** @test */
    public function it_should_trigger_save_on_exception()
    {
        $this->mock(Save::class)->shouldReceive('handle');

        $event = new MessageLogged('error', 'test', ['exception' => 'some exception']);

        event($event);
    }

    /** @test */
    public function it_should_not_trigger_save_if_message_log_does_not_have_an_exception()
    {
        $this->mock(Save::class)->shouldNotReceive('handle');

        $event = new MessageLogged('error', 'test');

        event($event);
    }

    /** @test */
    public function it_should_trigger_save_when_command_finished()
    {
        $this->mock(Save::class)->shouldReceive('handle');

        $initialEvent = new CommandStarting(
            'test',
            Mockery::mock(InputInterface::class),
            Mockery::mock(OutputInterface::class),
        );

        event($initialEvent);

        $event = new CommandFinished(
            'test',
            Mockery::mock(InputInterface::class),
            Mockery::mock(OutputInterface::class),
            0,
        );

        event($event);
    }

    /** @test */
    public function it_should_trigger_save_when_job_finished()
    {
        $this->mock(Save::class)->shouldReceive('handle');

        $event = new JobProcessed('test', Mockery::mock(Job::class));

        event($event);
    }

    private function getDbConnectionMock()
    {
        return \Mockery::mock(SQLiteConnection::class)
            ->shouldReceive('getName')
            ->andReturn('ConnectionName')
            ->getMock();
    }
}
