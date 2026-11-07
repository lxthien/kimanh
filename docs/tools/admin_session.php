<?php
// Usage: php admin_session.php <project_dir>  -> prints "<session_name>=<session_id>"
$dir = $argv[1];
chdir($dir);
require $dir . '/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->loadEnv($dir . '/.env');

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$c = $kernel->getContainer();
$user = $c->get('doctrine')->getRepository(App\Entity\User::class)->findOneBy([]);
$token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
$session = $c->get('session');
$session->start();
$session->set('_security_main', serialize($token));
$session->save();
fwrite(STDOUT, $session->getName() . '=' . $session->getId() . "\n");
