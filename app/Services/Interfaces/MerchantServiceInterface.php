<?php

namespace App\Services\Interfaces;

use App\Models\Merchant;

interface MerchantServiceInterface {
    public function registerForCurrentUser(array $data): Merchant;
    public function get(int $id): Merchant;
    public function updateForCurrentUser(int $id, array $data): Merchant;
    public function setApproval(int $id, string $status): Merchant;
}
