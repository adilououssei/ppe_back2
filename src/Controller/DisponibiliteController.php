<?php
// src/Controller/DisponibiliteController.php
namespace App\Controller;

use App\Entity\Creneau;
use App\Entity\Disponibilite;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DisponibiliteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/api')]
class DisponibiliteController extends AbstractController
{
    #[Route('/disponibilites', name: 'get_disponibilites', methods: ['GET'])]
    public function index(Request $request, DisponibiliteRepository $repo): JsonResponse
    {
        $docteurId = $request->query->get('docteur');

        if (!$docteurId) {
            return new JsonResponse(['error' => 'ID du docteur manquant'], Response::HTTP_BAD_REQUEST);
        }

        $disponibilites = $repo->findBy(['docteur' => $docteurId]);

        $formattedData = [];
        foreach ($disponibilites as $dispo) {
            $creneaux = [];
            foreach ($dispo->getCreneaus() as $creneau) {
                $creneaux[] = [
                    'id' => $creneau->getId(),
                    'debut' => $creneau->getDebut() ? $creneau->getDebut()->format('H:i') : null,
                    'fin' => $creneau->getFin() ? $creneau->getFin()->format('H:i') : null,
                    'type' => $creneau->getType(),
                ];
            }

            $formattedData[] = [
                'id' => $dispo->getId(),
                'date' => $dispo->getDate()->format('Y-m-d'),
                'creneaux' => $creneaux,
            ];
        }


        return new JsonResponse($formattedData);
    }


    #[Route('/disponibilites', name: 'add_disponibilite', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user || !in_array('ROLE_DOCTEUR', $user->getRoles())) {
                return new JsonResponse(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
            }

            $docteur = $em->getRepository(\App\Entity\Docteur::class)->findOneBy(['user' => $user]);
            if (!$docteur) {
                return new JsonResponse(['error' => 'Docteur introuvable.'], Response::HTTP_BAD_REQUEST);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['date'])) {
                return new JsonResponse(['error' => 'Date manquante.'], Response::HTTP_BAD_REQUEST);
            }

            $disponibilite = new Disponibilite();
            $disponibilite->setDate(new \DateTime($data['date']));
            $disponibilite->setDocteur($docteur);

            // Traitement des créneaux
            if (isset($data['creneaux']) && is_array($data['creneaux'])) {
                foreach ($data['creneaux'] as $creneauData) {
                    $creneau = new \App\Entity\Creneau();

                    // Attention ici aux clés qui doivent correspondre à ce que tu envoies du frontend
                    if (isset($creneauData['debut'])) {
                        $creneau->setDebut(new \DateTime($creneauData['debut']));
                    }
                    if (isset($creneauData['fin'])) {
                        $creneau->setFin(new \DateTime($creneauData['fin']));
                    }
                    if (isset($creneauData['type'])) {
                        $creneau->setType($creneauData['type']);
                    }
                    $creneau->setDisponibilite($disponibilite);

                    $em->persist($creneau);
                    $disponibilite->addCreneau($creneau);
                }
            }

            $em->persist($disponibilite);
            $em->flush();

            $json = $serializer->serialize($disponibilite, 'json', ['groups' => 'disponibilite']);
            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/disponibilite/{id}', name: 'delete_disponibilite', methods: ['DELETE'])]
    public function delete(Disponibilite $disponibilite, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($disponibilite);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/disponibilite/{id}', name: 'update_disponibilite', methods: ['PUT'])]
    public function update(Request $request, Disponibilite $disponibilite, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $updatedDisponibilite = $serializer->deserialize($request->getContent(), Disponibilite::class, 'json', ['object_to_populate' => $disponibilite]);
        $em->persist($updatedDisponibilite);
        $em->flush();

        $json = $serializer->serialize($updatedDisponibilite, 'json', ['groups' => 'disponibilite']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
