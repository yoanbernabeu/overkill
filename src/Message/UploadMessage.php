<?php

namespace App\Message;

class UploadMessage
{
    private $upload;
    private $user;

    public function __construct(string $upload, string $user)
    {
        $this->upload = $upload;
        $this->user = $user;
    }

    public function getUpload(): string
    {
        return $this->upload;
    }

    public function getUser(): string
    {
        return $this->user;
    }
}