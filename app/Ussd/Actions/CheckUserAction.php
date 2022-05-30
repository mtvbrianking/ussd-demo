<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CheckUserAction
{
    protected \DOMNode $node;
    protected CacheContract $cache;
    protected string $prefix;
    protected int $ttl;

    public function __construct(\DOMNode $node, CacheContract $cache, string $prefix, ?int $ttl = null)
    {
        $this->node = $node;
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    public function handle(): ?string
    {
        return '';
    }

    public function process(?string $answer): void
    {
        $phoneNumber = $this->cache->get("{$this->prefix}_phone_number");

        $user = User::where('phone_number', $phoneNumber)->first();

        if(! $user) {
            throw new \Exception("{$phoneNumber} is not registered for this service.");
        }

        if(! $user->pin_code) {
            throw new \Exception("{$phoneNumber} is not activated for this service.");
        }

        $this->cache->put("{$this->prefix}_user_id", $user->id, $this->ttl);
    }
}
