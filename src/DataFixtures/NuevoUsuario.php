<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NuevoUsuario extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $nombre = 'user@test.com';
        $plainPassword = '12345678';
        $user = new User();
        $user->setEmail($nombre);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        // Encriptación de la contraseña
        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);
        $user->setToken(bin2hex(random_bytes(32)));
        // Guarda el registro en la base de datos.
        $manager->persist($user);
        $manager->flush();
    }
}
