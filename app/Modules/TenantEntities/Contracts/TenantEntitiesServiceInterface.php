<?php

namespace App\Modules\TenantEntities\Contracts;

interface TenantEntitiesServiceInterface
{
    public function getByJid(string $jid): array;

    public function getByEspoCrmId(string $espocrmId): array;
}
