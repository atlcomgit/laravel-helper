<?php

require __DIR__ . '/vendor/autoload.php';

use Atlcom\Dto;

class TestDto extends Dto
{
    public ?string $userId       = null;
    public ?string $errorMessage = null;
}

$dto = TestDto::create(['userId' => '123', 'errorMessage' => 'error']);
print_r($dto->toArray());

//?!? delete
