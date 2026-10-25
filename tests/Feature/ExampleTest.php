<?php

test('returns a successful response for the lobby', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
