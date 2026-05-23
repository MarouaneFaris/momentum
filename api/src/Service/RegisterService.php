<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {}

    public function register(RegisterDTO $dto): void
    {
        $email = mb_strtolower($dto->email);
        $hash = $this->passwordHasher->hashPassword(new User(), $dto->password);
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            // @todo: send email
            return;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hash);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // @todo: send email
    }
}
