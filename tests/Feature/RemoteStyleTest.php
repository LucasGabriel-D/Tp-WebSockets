<?php

use App\Events\RemoteStyleUpdate;
use Illuminate\Support\Facades\Event;

test('the emitter form is shown', function () {
    $this->get(route('emisor'))
        ->assertOk()
        ->assertSee(route('emitir'));
});

test('the receiver page is shown', function () {
    $this->get(route('receptor'))->assertOk();
});

test('emitting broadcasts the text and style on the public style channel', function () {
    Event::fake([RemoteStyleUpdate::class]);

    $this->from(route('emisor'))
        ->post(route('emitir'), ['newText' => 'Ahora atendiendo: A-037', 'newStyle' => 'color:#ff0'])
        ->assertRedirect(route('emisor'))
        ->assertSessionHas('ok');

    Event::assertDispatched(RemoteStyleUpdate::class, fn (RemoteStyleUpdate $e) => $e->newText === 'Ahora atendiendo: A-037'
        && $e->newStyle === 'color:#ff0'
        && $e->broadcastOn()[0]->name === 'style-channel'
        && $e->broadcastAs() === 'RemoteStyleUpdate');
});

test('the style is optional', function () {
    Event::fake([RemoteStyleUpdate::class]);

    $this->post(route('emitir'), ['newText' => 'Hola'])->assertSessionHasNoErrors();

    Event::assertDispatched(RemoteStyleUpdate::class, fn (RemoteStyleUpdate $e) => $e->newStyle === '');
});

test('the text is required', function () {
    Event::fake([RemoteStyleUpdate::class]);

    $this->post(route('emitir'), ['newStyle' => 'color:red'])->assertSessionHasErrors('newText');

    Event::assertNotDispatched(RemoteStyleUpdate::class);
});
