<?php

test('registration is not available', function () {
    $this->get('/register')->assertNotFound();
});
