<?php

test('`/` path can be loaded', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
