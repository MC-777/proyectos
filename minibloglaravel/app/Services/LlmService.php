<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LlmService
{
    protected $provider;
    protected $config;

    public function __construct()
    {
        $this->config = config('services.llm');
        $this->provider = $this->config['provider'];
    }

    public function generarResumen(string $titulo, string $contenido, array $comentarios): string
    {
        $textoComentarios = empty($comentarios) 
            ? "Sin comentarios." 
            : collect($comentarios)->pluck('contenido')->implode("\n- ");

        $prompt = <<<EOD
        Actúa como un analista de contenido profesional. 
        Resume el siguiente post de un blog y analiza la opinión general de sus comentarios en un formato JSON limpio con dos llaves: 'resumen_post' y 'analisis_comentarios'. 
        Do not include markdown wrappers like ```json.

        POST TITULO: {$titulo}
        POST CONTENIDO: {$contenido}
        COMENTARIOS DEL PUBLICO:
        - {$textoComentarios}
        EOD;

        return match ($this->provider) {
            'gemini'    => $this->llamarGemini($prompt),
            default     => 'Proveedor de IA no configurado correctamente.'
        };
    }

private function llamarGemini($prompt)
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'response_mime_type' => 'application/json',
            'temperature' => 0.2
        ]
    ];

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'x-goog-api-key' => $this->config['gemini_key']
    ])->post($url, $payload);

    if ($response->failed()) {
        \Illuminate\Support\Facades\Log::error("Error real devuelto por Google Gemini: " . $response->body());
        return 'Error interno en la comunicación con el servidor de Google Gemini. Verifica los logs.';
    }

    return $response->json('candidates.0.content.parts.0.text') ?? 'Gemini devolvió una estructura vacía.';
}

}
