<?php

namespace App\DataFixtures;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NuevoUsuario extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $email = 'user@test.com';
        $username = 'testuser';
        $plainPassword = '12345678';
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        // Encriptación de la contraseña
        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);
        $user->setIsActive(true);
        $user->setStatus('active');
        $user->setVisibility('public');
        // Guarda el registro en la base de datos.
        $manager->persist($user);
        $manager->flush();
    }
}
