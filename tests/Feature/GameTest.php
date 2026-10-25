<?php

use App\Events\CombateFinalizado;
use App\Events\SalaActualizada;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

test('guests can view the lobby', function () {
    $response = $this->get(route('lobby'));

    $response->assertOk()
        ->assertSee('NEO')
        ->assertSee('DOJO')
        ->assertSee(route('enter'));
});

test('guests can enter with a nickname and are authenticated', function () {
    $response = $this->post(route('enter'), [
        'name' => 'SubZero',
    ]);

    $response->assertRedirect(route('lobby'));
    $this->assertAuthenticated();

    $user = auth()->user();
    expect($user->name)->toBe('SubZero');
});

test('authenticated users can create a room that is announced in the lobby', function () {
    Event::fake([SalaActualizada::class]);
    $user = User::factory()->create(['name' => 'Scorpion']);

    $response = $this->actingAs($user)->post(route('rooms.create'), ['char' => 2]);

    $response->assertRedirect();
    $targetUrl = $response->headers->get('Location');
    expect($targetUrl)->toMatch('/\/sala\/[A-Z0-9]{5}/');

    preg_match('/\/sala\/([A-Z0-9]{5})/', $targetUrl, $matches);
    $code = $matches[1];

    $room = Cache::get("room:$code");
    expect($room)->not->toBeNull();
    expect($room['players'])->toBe([$user->id]);

    Event::assertDispatched(SalaActualizada::class, fn (SalaActualizada $e) => $e->code === $code
        && $e->host === 'Scorpion'
        && $e->char === 2
        && $e->estado === 'abierta');

    $this->get(route('lobby'))->assertViewHas('salas', fn (array $salas) => $salas[0]['code'] === $code);
});

test('two players can enter the room and third is rejected', function () {
    Event::fake([SalaActualizada::class]);
    [$player1, $player2, $player3] = User::factory()->count(3)->create();

    $code = 'TEST1';
    Cache::put("room:$code", ['host' => $player1->name, 'players' => [$player1->id], 'chars' => [$player1->id => 0]], now()->addHours(2));

    $this->actingAs($player1)->get(route('room', $code))
        ->assertOk()->assertViewHas('game', fn (array $game) => $game['slot'] === 0);

    $this->actingAs($player2)->get(route('room', $code))
        ->assertOk()->assertViewHas('game', fn (array $game) => $game['slot'] === 1);

    expect(Cache::get("room:$code")['players'])->toBe([$player1->id, $player2->id]);

    $this->actingAs($player3)->get(route('room', $code))->assertForbidden();
});

test('a room disappears from the lobby once the second player joins', function () {
    Event::fake([SalaActualizada::class]);
    [$host, $guest] = User::factory()->count(2)->create();

    $location = $this->actingAs($host)->post(route('rooms.create'))->headers->get('Location');
    $code = basename($location);

    $this->actingAs($guest)->get(route('room', $code))->assertOk();

    Event::assertDispatched(SalaActualizada::class, fn (SalaActualizada $e) => $e->code === $code && $e->estado === 'llena');
    $this->get(route('lobby'))->assertViewHas('salas', []);
});

test('authenticated user can join via code form', function () {
    $user = User::factory()->create();
    $code = 'ALPHA';
    Cache::put("room:$code", ['players' => [999]], now()->addHours(2));

    $response = $this->actingAs($user)->post(route('rooms.join'), [
        'code' => 'alpha',
    ]);

    $response->assertRedirect(route('room', 'ALPHA'));
});

describe('match results', function () {
    beforeEach(function () {
        Event::fake([CombateFinalizado::class]);
        [$this->ryu, $this->ken] = User::factory()->count(2)->sequence(['name' => 'Ryu'], ['name' => 'Ken'])->create();
        Cache::put('room:RES01', [
            'host' => 'Ryu',
            'players' => [$this->ryu->id, $this->ken->id],
            'chars' => [$this->ryu->id => 0, $this->ken->id => 3],
        ], now()->addHours(2));
    });

    test('the first report is broadcast to the lobby and the duplicate is ignored', function () {
        $payload = ['match' => 1, 'winner' => 1, 'wins' => [1, 2], 'perfect' => true];

        $this->actingAs($this->ryu)->postJson(route('rooms.result', 'RES01'), $payload)
            ->assertStatus(202);
        $this->actingAs($this->ken)->postJson(route('rooms.result', 'RES01'), $payload)
            ->assertOk()->assertJson(['emitido' => false]);

        Event::assertDispatchedTimes(CombateFinalizado::class, 1);
        Event::assertDispatched(CombateFinalizado::class, fn (CombateFinalizado $e) => $e->broadcastWith() === [
            'ganador' => 'Ken',
            'perdedor' => 'Ryu',
            'charGanador' => 3,
            'charPerdedor' => 0,
            'marcador' => '2-1',
            'perfect' => true,
        ]);
        $this->get(route('lobby'))->assertViewHas('resultados', fn (array $r) => count($r) === 1 && $r[0]['ganador'] === 'Ken');
    });

    test('a rematch is reported as a new result', function () {
        foreach ([1, 2] as $match) {
            $this->actingAs($this->ryu)->postJson(route('rooms.result', 'RES01'), ['match' => $match, 'winner' => 0, 'wins' => [2, 0]])
                ->assertStatus(202);
        }

        Event::assertDispatchedTimes(CombateFinalizado::class, 2);
    });

    test('players outside the room cannot report a result', function () {
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->postJson(route('rooms.result', 'RES01'), ['match' => 1, 'winner' => 0, 'wins' => [2, 0]])
            ->assertForbidden();

        Event::assertNotDispatched(CombateFinalizado::class);
    });

    test('the result payload is validated', function () {
        $this->actingAs($this->ryu)->postJson(route('rooms.result', 'RES01'), ['match' => 1, 'winner' => 2, 'wins' => [2]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['winner', 'wins']);

        Event::assertNotDispatched(CombateFinalizado::class);
    });
});
