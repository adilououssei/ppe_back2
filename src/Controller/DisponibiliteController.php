<?php
// src/Controller/DisponibiliteController.php
namespace App\Controller;

use App\Entity\Creneau;
use App\Entity\Disponibilite;
use App\Entity\Docteur;
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
    /**
     * GET /api/disponibilites
     * 
     * Si c'est un docteur connecté, on renvoie uniquement ses disponibilités.
     * Sinon, un patient peut passer ?docteur={id} pour voir les disponibilités d’un docteur.
     */
    #[Route('/disponibilites', name: 'get_disponibilites', methods: ['GET'])]
    public function index(Request $request, DisponibiliteRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $docteurParam = $request->query->get('docteur');

        if ($user && in_array('ROLE_DOCTEUR', $user->getRoles())) {
            // Docteur connecté → voit uniquement ses disponibilités
            $docteur = $em->getRepository(Docteur::class)->findOneBy(['user' => $user]);
            if (!$docteur) {
                return new JsonResponse(['error' => 'Docteur introuvable.'], Response::HTTP_BAD_REQUEST);
            }
        } elseif ($docteurParam) {
            // Patient → peut voir les disponibilités d’un docteur choisi
            $docteur = $em->getRepository(Docteur::class)->find($docteurParam);
            if (!$docteur) {
                return new JsonResponse(['error' => 'Docteur introuvable.'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return new JsonResponse(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $disponibilites = $repo->findBy(['docteur' => $docteur]);

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

    // --- Création de disponibilité pour docteur ---
    #[Route('/disponibilites', name: 'add_disponibilite', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_DOCTEUR', $user->getRoles())) {
            return new JsonResponse(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $docteur = $em->getRepository(Docteur::class)->findOneBy(['user' => $user]);
        if (!$docteur) {
            return new JsonResponse(['error' => 'Docteur introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['date'])) {
                return new JsonResponse(['error' => 'Date manquante.'], Response::HTTP_BAD_REQUEST);
            }

            $date = new \DateTime($data['date']);

            // Vérifie si une disponibilité existe déjà pour ce docteur et cette date
            $disponibilite = $em->getRepository(Disponibilite::class)
                ->findOneBy(['docteur' => $docteur, 'date' => $date]);

            if (!$disponibilite) {
                $disponibilite = new Disponibilite();
                $disponibilite->setDate($date);
                $disponibilite->setDocteur($docteur);
                $em->persist($disponibilite);
            }

            // Ajout des créneaux
            if (!empty($data['creneaux']) && is_array($data['creneaux'])) {
                foreach ($data['creneaux'] as $creneauData) {
                    $creneau = new Creneau();

                    if (!empty($creneauData['debut'])) {
                        $creneau->setDebut(new \DateTime($creneauData['debut']));
                    }
                    if (!empty($creneauData['fin'])) {
                        $creneau->setFin(new \DateTime($creneauData['fin']));
                    }

                    $creneau->setType($creneauData['type'] ?? 'consultation');
                    $creneau->setDisponibilite($disponibilite);

                    $em->persist($creneau);
                    $disponibilite->addCreneau($creneau);
                }
            }

            $em->flush();

            $json = $serializer->serialize($disponibilite, 'json', ['groups' => 'disponibilite']);
            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


    // --- Mise à jour d'une disponibilité ---
    #[Route('/disponibilites/{id}', name: 'update_disponibilite', methods: ['PUT'])]
    public function update(Request $request, Disponibilite $disponibilite, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_DOCTEUR', $user->getRoles()) || $disponibilite->getDocteur()->getUser() !== $user) {
            return new JsonResponse(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $updatedDisponibilite = $serializer->deserialize($request->getContent(), Disponibilite::class, 'json', ['object_to_populate' => $disponibilite]);
        $em->persist($updatedDisponibilite);
        $em->flush();

        $json = $serializer->serialize($updatedDisponibilite, 'json', ['groups' => 'disponibilite']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    // --- Suppression d'une disponibilité ---
    #[Route('/disponibilites/{id}', name: 'delete_disponibilite', methods: ['DELETE'])]
    public function delete(Disponibilite $disponibilite, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_DOCTEUR', $user->getRoles()) || $disponibilite->getDocteur()->getUser() !== $user) {
            return new JsonResponse(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($disponibilite);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
