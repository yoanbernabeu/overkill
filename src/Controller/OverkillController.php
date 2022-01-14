<?php

namespace App\Controller;

use App\Entity\Upload;
use App\Form\UploadType;
use App\Message\UploadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class OverkillController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="overkill")
     */
    public function index(Request $request, MessageBusInterface $bus, UploaderHelper $helper): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $upload = new Upload();

        $form = $this->createForm(UploadType::class, $upload);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $upload->setUploadBy($this->getUser());
            $this->entityManager->persist($upload);
            $this->entityManager->flush();

            $bus->dispatch(new UploadMessage($upload->getImageFile(), $this->getUser()->getUserIdentifier()));

            return $this->redirectToRoute('overkill');
        }

        return $this->render('overkill/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
