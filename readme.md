# Projet OverKill

Projet réaliser dans le cadre d'une série pour la chaine Youtube [YoanDev](https://www.youtube.com/c/yoandevco).

Ce projet est volontairement **overkill** et fait usage d'une débauche de techno, uniquement dans un objectif récréatif.

## Création du projet Symfony

```shell
symfony new OverKill --full
cd OverKill
symfony serve -d
```

## Création du Docker-compose

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

## Installation de Webpack Encore et Pico.css

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

## Création d'un controller *overkill*

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

## Entité, Vich Uploader et Stockage Objet

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

Et enfin, affichon le dans notre page, d'abord le controlleur

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