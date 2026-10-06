<?php
namespace App\Core;

// ============================================================
//  Passerelle de paiement Stripe — MODE TEST uniquement.
//  L'appel à config('stripe.secret_key') avec une clé VIDE
//  désactive Stripe : le contrôleur bascule alors sur le
//  paiement simulé (aucune dépendance).
//
//  💳 Conformité RGPD : le client tape sa carte sur la page
//  hébergée par Stripe (Checkout). Notre serveur ne reçoit
//  et ne stocke JAMAIS de données bancaires.
// ============================================================
final class StripeGateway
{
    private const API_URL = 'https://api.stripe.com/v1';

    // Une clé sk_test_… est-elle configurée ? (si non → paiement simulé)
    public static function configuree(): bool
    {
        $cle = (string)(config('stripe.secret_key') ?? '');
        return $cle !== '';
    }

    // Crée une session Checkout et renvoie l'URL Stripe à suivre,
    // ou null en cas d'échec (clé invalide, hors-ligne…).
    public static function creerSessionCheckout(string $nomProduit, float $montant, string $urlSucces, string $urlAnnulation): ?string
    {
        $reponse = self::requete('POST', '/checkout/sessions', [
            'mode'                 => 'payment',
            'success_url'          => $urlSucces,
            'cancel_url'           => $urlAnnulation,
            'line_items[0][quantity]'                        => '1',
            'line_items[0][price_data][currency]'            => 'eur',
            'line_items[0][price_data][unit_amount]'         => (string)round($montant * 100),
            'line_items[0][price_data][product_data][name]'  => $nomProduit,
        ]);

        if ($reponse === null) {
            return null;
        }
        $data = json_decode($reponse, true);
        if (!is_array($data)) {
            return null;
        }
        return $data['url'] ?? null;
    }

    // Vérifie côté Stripe qu'une session a bien été payée.
    public static function sessionPayee(string $sessionId): bool
    {
        $reponse = self::requete('GET', '/checkout/sessions/' . rawurlencode($sessionId), []);
        if ($reponse === null) {
            return false;
        }
        $data = json_decode($reponse, true);
        return is_array($data) && ($data['payment_status'] ?? '') === 'paid';
    }

    // Requête HTTPS vers l'API Stripe (sans SDK : simple cURL).
    private static function requete(string $methode, string $chemin, array $params): ?string
    {
        $ch = curl_init(self::API_URL . $chemin);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . (string)config('stripe.secret_key')],
        ]);
        if ($methode === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $reponse = curl_exec($ch);
        $errno   = curl_errno($ch);
        curl_close($ch);
        return $errno !== 0 ? null : $reponse;
    }
}