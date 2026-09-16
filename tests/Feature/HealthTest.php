<?php

it('exposes the application health endpoint', function () {
    $this->get('/up')->assertOk();
});
