<?php

namespace App\Controller;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Product;
use App\Entity\Contact;
use App\Service\FileUploader;
use App\Form\SanPhamType;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


class AdminController extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    #[Route('/', name: 'admin')]
    public function adminRequirement()
    {
        return $this->render('admin/index.html.twig');
    
    }
    #[Route('/admin/listsp', name: 'list_product')]
    public function list_sp(EntityManagerInterface $em, Request $req): Response
    {
        $query = $em->createQuery('SELECT sp FROM App\Entity\Product sp');
        $lSp = $query->getResult();
        $message = $req->query->get('message');
        return $this->render('admin/list.html.twig', [
            "data"=>$lSp,
            "message"=>$message
        ]);
    }
    #[Route('/mnproduct', name: 'addpd')]
    public function addProduct(EntityManagerInterface $em, Request $req, FileUploader $fileUploader, Security $security): Response
    {
        $sp = new Product();
        $form = $this->createForm(SanPhamType::class, $sp);
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $file = $form->get("image")->getData();

            $fileName = $fileUploader->upload($file);
            $data->setImage($fileName);

            $em->persist($data);
            $em->flush();
            return new RedirectResponse($this->urlGenerator->generate('list_product'));
        }

        return $this->render('admin/index.html.twig', [
            'sp_form' => $form->createView(),
        ]);
    }
    #[Route('/san/pham/{id}', name: 'app_edit_san_pham')]
    public function edit(EntityManagerInterface $em, int $id, Request $req, FileUploader $fileUploader): Response
    {
        $sp = $em->find(Product::class, $id);
        $form = $this->createForm(SanPhamType::class, $sp);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $file = $form->get("image")->getData();
            if ($file) {
                $fileName = $fileUploader->upload($file);
                $data->setImage($fileName);
            }
            $sp->setName($data->getName())->setPrice($data->getPrice());
            $em->flush();
            return new RedirectResponse($this->urlGenerator->generate('list_product'));
        }

        return $this->render('admin/index.html.twig', [
            'sp_form' => $form->createView(),
        ]);
    }

    #[Route('/san/pham/{id}/delete', name: 'app_delete_san_pham')]
    public function delete(EntityManagerInterface $em, int $id, Request $req): Response
    {
        $sp = $em->find(Product::class, $id);
        $em->remove($sp);
        $em->flush();
        return new RedirectResponse($this->urlGenerator->generate('list_product'));
    }
}


