<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterDTO;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegisterService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {}

    public function register(RegisterDTO $dto): void
    {
        $email = mb_strtolower($dto->email);
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            // @todo: send email
            return;
        }

        $hash = $this->passwordHasher->hashPassword(new User(), $dto->password);

        $this->entityManager->wrapInTransaction(function () use ($email, $hash): void {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($hash);
            $this->entityManager->persist($user);

            $workspace = new Workspace();
            $workspace->setName(explode('@', $email)[0] . "'s workspace");
            $workspace->setCreator($user);
            $this->entityManager->persist($workspace);

            $userWorkspace = new UserWorkspace();
            $userWorkspace->setUser($user);
            $userWorkspace->setWorkspace($workspace);
            $userWorkspace->setRole(WorkspaceRole::Owner);
            $this->entityManager->persist($userWorkspace);

            $this->entityManager->flush();
        });

        // @todo: send email
    }
}
