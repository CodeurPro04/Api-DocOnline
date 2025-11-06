<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Medecin;
use Illuminate\Support\Facades\Log;

class MedicalChatController extends Controller
{
    public function diagnose(Request $request)
    {
        $message = trim($request->input('message'));

        if (!$message) {
            return response()->json(['message' => 'Veuillez décrire vos symptômes.'], 400);
        }

        $prompt = <<<EOT
Tu es MeetMed, un assistant médical virtuel intelligent, empathique et professionnel.  
Analyse le message du patient et détecte tous les symptômes. Propose uniquement la spécialité médicale la plus adaptée.  

Règles :
1. Si le message est un simple salut ou commentaire sans symptôme ("Bonjour", "Merci", etc.), répond humainement et invite le patient à décrire ses symptômes, sans JSON.
2. Si le message contient des symptômes, répond uniquement en JSON strict :
{
  "specialite": "Nom exact de la spécialité médicale",
  "raison": "Explication concise et claire du choix basé sur les symptômes"
}
3. Sois empathique, clair, poli et professionnel.  
4. Ne propose jamais de traitement, uniquement la spécialité.  
5. Utilise les noms exacts de spécialités : Médecine générale, Cardiologue, Neurologue, Dermatologue, Psychiatre, Pédiatre, etc.

Message du patient : "$message"
EOT;

        try {
            Log::info('MedicalChatController: Envoi de la requête à OpenAI', ['message' => $message]);

            $apiKey = config('services.openai.api_key');

            // SOLUTION : Utiliser Http Facade avec SSL désactivé
            $response = Http::withOptions([
                'verify' => false, // Désactive SSL
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'temperature' => 0.3,
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un médecin virtuel professionnel et bienveillant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->failed()) {
                throw new \Exception('Erreur API: ' . $response->status());
            }

            $responseData = $response->json();
            $aiText = $responseData['choices'][0]['message']['content'] ?? '';

            Log::info('MedicalChatController: Texte IA', ['text' => $aiText]);

            $data = json_decode($aiText, true);

            if (!$data || !isset($data['specialite'])) {
                return response()->json([
                    'message' => $aiText,
                ], 200);
            }

            $specialite = $data['specialite'];

            $medecins = Medecin::where('specialite', 'like', "%{$specialite}%")
                ->select('id', 'nom', 'prenom', 'specialite', 'email', 'telephone', 'address', 'photo_profil')
                ->take(3)
                ->get();

            return response()->json([
                'specialite' => $specialite,
                'raison' => $data['raison'] ?? null,
                'medecins' => $medecins,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur MedicalChatController', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur interne du chatbot. Consultez le log pour plus de détails.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}