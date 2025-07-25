<?php

namespace Test\PhpDevCommunity\Controller;

use PhpDevCommunity\Attribute\Route;
use PhpDevCommunity\Attribute\ControllerRoute;

#[ControllerRoute('/api', format: 'json')]
class PingController
{
    #[Route('ping', name: 'ping', methods: ['GET'])]
    public function ping(): string
    {
        return json_encode(['pong' => true]);
    }
}
