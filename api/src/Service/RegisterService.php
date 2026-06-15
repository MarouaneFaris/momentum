<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterDTO;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Message\SendVerificationEmail;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegisterService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private MessageBusInterface $bus,
    ) {}

    public function register(RegisterDTO $dto): void
    {
        $email = mb_strtolower($dto->email);
        $name = $dto->name;
        $hash = $this->passwordHasher->hashPassword(new User(), $dto->password);
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            return;
        }

        $userId = null;

        $this->entityManager->wrapInTransaction(function () use ($email, $name, $hash, &$userId): void {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setPassword($hash);
            $this->entityManager->persist($user);

            $workspace = new Workspace();
            $workspace->setName("{$name}'s workspace");
            $workspace->setCreator($user);
            $this->entityManager->persist($workspace);

            $userWorkspace = new UserWorkspace();
            $userWorkspace->setUser($user);
            $userWorkspace->setWorkspace($workspace);
            $userWorkspace->setRole(WorkspaceRole::Owner);
            $this->entityManager->persist($userWorkspace);

            $this->entityManager->flush();
            $userId = (string) $user->getId();
        });

        if ($userId !== null) {
            $this->bus->dispatch(new SendVerificationEmail($userId));
        }
    }
}
