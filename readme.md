# Projet OverKill

Projet réaliser dans le cadre d'une série pour la chaine Youtube [YoanDev](https://www.youtube.com/c/yoandevco).

Ce projet est volontairement **overkill** et fait usage d'une débauche de techno, uniquement dans un objectif récréatif.

## 1 - Création du projet Symfony

```shell
symfony new OverKill --full
cd OverKill
symfony serve -d
```

## 2 - Création du Docker-compose

On ajoute **Redis**

```yaml
  redis:
    image: redis:5-alpine
    ports: [6379]
```

Puis **n8n**

```yaml
  n8n:
    image: n8nio/n8n
    ports:
      - 5678:5678
```

Puis **Imaginary**

```yaml
  imaginary:
    image: h2non/imaginary:latest
    volumes:
      - ./images:/mnt/data
    environment:
       PORT: 9000
    command: -enable-url-source -mount /mnt/data
    ports:
      - "9000:9000"
```

N'oublions pas **RabbitMQ**

```yaml
rabbitmq:
    image: rabbitmq:3.7-management
    ports: [5672, 15672]
```

Et enfin, **MinIO**

```yaml
    minio:
        image: minio/minio
        environment:
            MINIO_ROOT_USER: access1234
            MINIO_ROOT_PASSWORD: secret1234
        volumes:
            - ./data/minio:/data
        command: server /data --console-address ":9001"
        ports:
            - 9090:9000
            - 9001:9001
```

Et finalement, nous pouvons démarrer l'ensemble

```bash
docker-compose up -d
```

## 3 - Installation de Webpack Encore et Pico.css

* Installons Webpack Encore et Pico.css

```
composer require symfony/webpack-encore-bundle
npm install
npm install @picocss/pico
```

- Modification du ```/assets/app.js```

```javascript
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';
```

- Rennomer ```/assets/styles/app.css``` en ```/assets/styles/app.scss```
- Installer ```npm install sass-loader@^12.0.0 sass --save-dev```
- Décommenter la ligne``` .enableSassLoader()``` dans le fichier ```webpack.config.js```
- Lancer la compilation en mode *watch* : ```npm run watch```
- Remplacer le contenu de ```/assets/styles/app.sccs``` par

```scss
@import "~@picocss/pico/scss/pico.scss";
```

- Modifier le fichier /templates/base.html.twig

```twig
<!DOCTYPE html>
<html id="theme" data-theme="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{% block title %}Welcome!{% endblock %}</title>
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>⚫️</text></svg>">
        {# Run `composer require symfony/webpack-encore-bundle` to start using Symfony UX #}
        {% block stylesheets %}
            {{ encore_entry_link_tags('app') }}
        {% endblock %}

        {% block javascripts %}
            {{ encore_entry_script_tags('app') }}
        {% endblock %}
    </head>
    <body>
        <main class="container">
            <!-- Header -->
            <header class="container">
                <hgroup>
                    <h1>OverKill</h1>
                    <h2>Un truc complètement OverKill pour le fun !</h2>
                </hgroup>
            </header>
            <!-- ./ Header -->
            {% block body %}{% endblock %}
        </main>
    </body>
</html>
```

## 4 - Création d'un controller *overkill*

Commençon par créer un controlleur du nom de **overkill**

```shell
symfony console make:controller overkill
```

Et modifions le :

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverkillController extends AbstractController
{
    /**
     * @Route("/", name="overkill")
     */
    public function index(): Response
    {
        return $this->render('overkill/index.html.twig', [
            'controller_name' => 'OverkillController',
        ]);
    }
}
```

## 5 - Entité, Vich Uploader et Stockage Objet

* Créons une entité *Upload*

```shell
symfony console make:entity Upload
 > imageName
symfony console make:migration
symfony console d:m:m 
```

* Installation Vich

```shell
composer require vich/uploader-bundle
```

* On paramètre Vich

```yaml
# config/packages/vich_uploader.yaml or app/config/config.yml
vich_uploader:
    db_driver: orm

    mappings:
        upload:
            uri_prefix: /upload
            upload_destination: '%kernel.project_dir%/public/upload'
```

* On adpate notre entitée pour utilise Vich

```php
<?php

namespace App\Entity;

use App\Repository\UploadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=UploadRepository::class)
 * @Vich\Uploadable
 */
class Upload
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $imageName;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
     * @Vich\UploadableField(mapping="upload", fileNameProperty="imageName")
     * 
     * @var File|null
     */
    private $imageFile;

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

}
```

* Créons un formulaire d'upload

```
symfony console make:form UploadType
 > Upload
```

Et modifions le :

```php
<?php

namespace App\Form;

use App\Entity\Upload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;


class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'label' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Upload',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Upload::class,
        ]);
    }
}

```

Et enfin, affichons le dans notre page, d'abord le controlleur

```php
    /**
     * @Route("/", name="overkill")
     */
    public function index(Request $request): Response
    {
        $upload = new Upload();

        $form = $this->createForm(UploadType::class, $upload);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($upload);
            $this->entityManager->flush();

            return $this->redirectToRoute('overkill');
        }

        return $this->render('overkill/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
```

Puis le fichier Twig

```twig
{% extends 'base.html.twig' %}

{% block title %}Hello OverkillController!{% endblock %}

{% block body %}
    {{ form(form) }}
{% endblock %}
```

Installons la surcouche pour utiliser MinIO

```
composer require league/flysystem-bundle
league/flysystem-aws-s3-v3
```

Et configurons Flysystem

```yaml
# /config/packages/flysystem.yaml
flysystem:
    storages:
        default.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/public/fichier'
        aws.storage:
            adapter: 'aws'
            options:
                client: Aws\S3\S3Client
                bucket: 'fichier'
```

Puis, modifions la configuration de Vich pour qu'il utilise Flysystem :

```yaml
vich_uploader:
    db_driver: orm
    storage: flysystem

    mappings:
        upload:
            uri_prefix: /upload
            upload_destination: aws.storage
```

Et enfin, déclarons un service dans **service.yml**

```yaml
    Aws\S3\S3Client:
        arguments:
            - version: 'latest'
              region: 'eu-east-1'
              endpoint: '127.0.0.1:9090'
              credentials:
                key: 'access1234'
                secret: 'secret1234'
```

Créon un Bucket **fichier** dans MinIO :

* Ouvrir une session
* Menu Buckets
* Bucket name: fichier

Testons à nouveau d'upload un fichier : il est dans MiniO :+1: 

## 6 - Création d'un Login/Register

* Création en premier lieu un **user**

```shell
symfony console make:user           

 The name of the security user class (e.g. User) [User]:
 > User

 Do you want to store user data in the database (via Doctrine)? (yes/no) [yes]:
 > yes

 Enter a property name that will be the unique "display" name for the user (e.g. email, username, uuid) [email]:
 > email

 Will this app need to hash/check user passwords? Choose No if passwords are not needed or will be checked/hashed by some other system (e.g. a single sign-on server).

 Does this app need to hash/check user passwords? (yes/no) [yes]:
 > yes

 created: src/Entity/User.php
 created: src/Repository/UserRepository.php
 updated: src/Entity/User.php
 updated: config/packages/security.yaml

           
  Success!
```

* Puis gérons les migrations

```
symfony console make:migration
symfony console d:m:m
```

* Créons un système de création de compte

```shell
symfony console make:registration-form

 Creating a registration form for App\Entity\User

 Do you want to add a @UniqueEntity validation annotation on your User class to make sure duplicate accounts aren't created? (yes/no) [yes]:
 > yes

 Do you want to send an email to verify the user's email address after registration? (yes/no) [yes]:
 > no

 Do you want to automatically authenticate the user after registration? (yes/no) [yes]:
 > yes

 ! [NOTE] No Guard authenticators found - so your user won't be automatically authenticated after registering.          

 What route should the user be redirected to after registration?:
  [0 ] _wdt
  [1 ] _profiler_home
  [2 ] _profiler_search
  [3 ] _profiler_search_bar
  [4 ] _profiler_phpinfo
  [5 ] _profiler_search_results
  [6 ] _profiler_open_file
  [7 ] _profiler
  [8 ] _profiler_router
  [9 ] _profiler_exception
  [10] _profiler_exception_css
  [11] overkill
  [12] _preview_error
 > 11

 updated: src/Entity/User.php
 created: src/Form/RegistrationFormType.php
 created: src/Controller/RegistrationController.php
 created: templates/registration/register.html.twig

           
  Success!
```

* On supprime le bout de code suivant du fichier ```src/Form/RegistrationFormType.php```

```php
/* On supprime cette portion de code */

->add('agreeTerms', CheckboxType::class, [
    'mapped' => false,
    'constraints' => [
        new IsTrue([
            'message' => 'You should agree to our terms.',
        ]),
    ],
])
```

* Et on supprime sont equivalent dans le fichier Twig ```templates/registration/register.html.twig```

```twig
{{ form_row(registrationForm.agreeTerms) }}
```

* On peut désormais consulter l'url ```/register``` de son application, et tester la création d'un compte !

* Et enfin, créons une page de  **login**

```shell
symfony console make:auth            

 What style of authentication do you want? [Empty authenticator]:
  [0] Empty authenticator
  [1] Login form authenticator
 > 1

 The class name of the authenticator to create (e.g. AppCustomAuthenticator):
 > AppAuthenticator

 Choose a name for the controller class (e.g. SecurityController) [SecurityController]:
 > SecurityController

 Do you want to generate a '/logout' URL? (yes/no) [yes]:
 > yes

 created: src/Security/AppAuthenticator.php
 updated: config/packages/security.yaml
 created: src/Controller/SecurityController.php
 created: templates/security/login.html.twig

           
  Success! 
```

* N'oublions pas de modifier le fichier ```src/Security/AppAuthenticator.php```

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        return new RedirectResponse($this->urlGenerator->generate('overkill'));
        //throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
```

* Et pour tout cela est un sens ^^, protégons la page ```overkill```, en ajoutons simplement cela à notre controller.

```php
$this->denyAccessUnlessGranted('ROLE_USER');
```

## 7 - Stockons la session dans REDIS

* Modifions le fichier ```config/services.yaml```

```yaml
services:

    # ...

    Redis:
        class: Redis
        calls:
            - connect:
                - '%env(REDIS_HOST)%'
                - '%env(int:REDIS_PORT)%'
                
    Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler:
        arguments:
            - '@Redis'
```

* Puis, le fichier ```config/packages/framework.yaml```

```yaml
# ...

    session:
        handler_id: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        cookie_secure: auto
        cookie_samesite: lax
        storage_factory_id: session.storage.factory.native

# ...
```

* Et... c'est tout, notre serveur Redis étant dèja lancé :)

## 8 - Créons une *jointure* entre *User* et *Upload*, et utilisons la lors d'un Upload

Comme notre objectif est d'envoyer par email le résultat de la transformation d'image à un utilisateur, créons une *jointure* entre les deux entités!

* Créons la relation entre les deux entités

```shell
symfony console make:entity Upload
                                                       
 New property name (press <return> to stop adding fields):
 > uploadBy

 Field type (enter ? to see all types) [string]:
 > relation

 What class should this entity be related to?:
 > User

 Relation type? [ManyToOne, OneToMany, ManyToMany, OneToOne]:
 > ManyToOne

 Is the Upload.uploadBy property allowed to be null (nullable)? (yes/no) [yes]:
 > yes

 Do you want to add a new property to User so that you can access/update Upload objects from it - e.g. $user->getUploads()? (yes/no) [yes]:
 > yes

 A new property will also be added to the User class so that you can access the related Upload objects from it.

 New field name inside User [uploads]:
 > uploads

 updated: src/Entity/Upload.php
 updated: src/Entity/User.php
    
  Success!
```

* Et comme d'habitude, on pense aux migrations !

```shell
symfony console make:migration
symfony console d:m:m:
```

* Utilisons cette relation lors de l'upload, en ajoutant un ```$upload->setUploadBy()``` lors de la soumission d'un formulaire d'upload.

```php
/**
     * @Route("/", name="overkill")
     */
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $upload = new Upload();

        $form = $this->createForm(UploadType::class, $upload);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $upload->setUploadBy($this->getUser());
            $this->entityManager->persist($upload);
            $this->entityManager->flush();

            return $this->redirectToRoute('overkill');
        }
        
        return $this->render('overkill/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
```

## 9 - Envoyons notre Upload et l'adresse Email dans un RabbitMQ

* Débutons pas le début, et installons le composant **Messenger**

```shell
composer require symfony/messenger
```

* Puis créons le **Message** et sont **Handler**

    * D'abord le **message** dans un fichier ```src/Message/UploadMessage.php```

    ```php
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
    ```

    * Puis le **handler**, dans le fichier ```src/MessageHandler/UploadMessageHandler.php```

    ```php
    <?php

    namespace App\MessageHandler;

    use App\Message\UploadMessage;
    use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

    class UploadMessageHandler implements MessageHandlerInterface
    {
        public function __invoke(UploadMessage $message)
        {
            dump($message);
        }
    }
    ```

* Ensuite, dispatchons un message lors de la soumission du formulaire d'upload.

```php
    # ...
    use Symfony\Component\Messenger\MessageBusInterface;

    # ...

    public function index(Request $request, MessageBusInterface $bus, UploaderHelper $helper): Response
    {
        # ...
        if ($form->isSubmitted() && $form->isValid()) {
            # ...
            $bus->dispatch(new UploadMessage($upload->getImageFile(), $this->getUser()->getUserIdentifier()));
            # ...
        }
    }
```

* Et pour finir, passons à l'utilisation de notre RabbitMQ et editant le fichier ```config/packages/messenger.yaml```

```yaml
framework:
    messenger:
        # Uncomment this (and the failed transport below) to send failed messages to this transport for later handling.
        # failure_transport: failed

        transports:
            # https://symfony.com/doc/current/messenger.html#transport-configuration
            async: '%env(RABBITMQ_DSN)%'
            # failed: 'doctrine://default?queue_name=failed'
            # sync: 'sync://'

        routing:
            # Route your messages to the transports
            'App\Message\UploadMessage': async
        serializer:
            default_serializer: messenger.transport.symfony_serializer
            symfony_serializer:
                format: json
                context: { }
```

* Nous pouvons faire un test est constater que le message est bien émis dans le bus de message RabbitMQ, avec les deux informations qui nous interesse (Le nom de l'upload, et l'adresse Email).

## 10 - De n8n aux Mails via Imaginary

Récupérons les messages RabbitMQ avec n8n, puis demandons une transformation de l'image (un carré par exemple) à Imagiary, et enfin, envoyons le résultat par mail à l'utilisateur.

* Rendons notre Bucket MinIO **Public**

* Ouvrons notre instance de n8n (sur le port 5678)

* Ajoutons un noeud **RabbitMQ Trigger**

    * Créons des crédentials pour notre instance RabbitMQ
        * Hostname : rabbitmq
        * Port : 5672
        * Login/pass: guest

    * Dans Queu/Topic: messages

    * Puis dans les options :
        * Activer **JSON Parse Body**
        * Activer **Only content**

* Ajoutons un noeud **HTTP Request**
    * Method : GET
    * URL : http://imaginary:9000/smartcrop?height=400&width=400&url=http://minio:9000/fichier/{{$json["upload"]}}
    * Response format: File
    * Binary property: image

* Ajoutons un noeud **Send Email**
    * Créons de crédentials pour notre mail
        * Host: mailer
        * Port: 1025
        * SSL/TLS : False
    * From: obi@wan.fr
    * To : {{$json["user"]}}
    * Subject/Text: Ton image bg !
    * Attachement: image

* On sauvegarde, et on lance le Workflow !

* TADA !