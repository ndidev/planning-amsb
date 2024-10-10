<?php

// Path: api/src/DTO/CurrentUserDTO.php

namespace App\DTO;

class CurrentUserFormDTO
{
    private ?string $uid = null;
    private ?string $name = null;
    private ?string $password = null;
    private ?string $passwordHash = null;

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        if ($this->password) {
            $this->passwordHash = password_hash($this->password, PASSWORD_DEFAULT);
        }

        return $this->passwordHash;
    }
}
