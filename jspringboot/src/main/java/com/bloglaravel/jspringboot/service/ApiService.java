package com.bloglaravel.jspringboot.service;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestClient;
import org.springframework.web.client.HttpClientErrorException;
import org.springframework.web.client.HttpServerErrorException;
import org.springframework.web.util.UriComponentsBuilder;
import java.net.URI;
import java.util.Map;

@Service
public class ApiService {

    private final String baseUrl;
    private final RestClient restClient;

    public ApiService(@Value("${laravel.api.url}") String baseUrl) {
        this.baseUrl = baseUrl.trim();
        this.restClient = RestClient.create();
    }

    public ResponseEntity<String> procesarPeticion(String metodo, String endpoint, String token, Object body) {
        try {
            String metodoLimpio = metodo.toUpperCase().trim();
            
            String urlBaseLimpia = baseUrl;
            if (!urlBaseLimpia.startsWith("http://") && !urlBaseLimpia.startsWith("https://")) {
                urlBaseLimpia = "http://" + urlBaseLimpia;
            }
            urlBaseLimpia = urlBaseLimpia.replace(":80", "");

            if (!urlBaseLimpia.endsWith("/api")) {
                urlBaseLimpia = urlBaseLimpia.endsWith("/") ? urlBaseLimpia + "api" : urlBaseLimpia + "/api";
            }

            String endpointLimpio = endpoint.trim();
            if (endpointLimpio.startsWith("/api/")) {
                endpointLimpio = endpointLimpio.substring(4);
            }
            if (!endpointLimpio.startsWith("/")) {
                endpointLimpio = "/" + endpointLimpio;
            }

            URI urlFinal = UriComponentsBuilder.fromUriString(urlBaseLimpia + endpointLimpio)
                    .build()
                    .toUri();

            RestClient.RequestBodySpec requestSpec = prepararPeticionBase(metodoLimpio, urlFinal, token);

            if (requestSpec == null) {
                return ResponseEntity.status(HttpStatus.METHOD_NOT_ALLOWED)
                        .body("{\"error\": \"Método HTTP no soportado en el proxy: " + metodoLimpio + "\"}");
            }

            if ("POST".equals(metodoLimpio) || "PUT".equals(metodoLimpio)) {
                requestSpec.contentType(MediaType.APPLICATION_JSON);
                if (body != null) {
                    requestSpec.body(body);
                } else {
                    requestSpec.body(Map.of());
                }
            }

            return requestSpec.retrieve().toEntity(String.class);

        } catch (HttpClientErrorException | HttpServerErrorException e) {
            System.err.println("🔴 Error controlado en API Laravel [" + metodo + " " + endpoint + "]: " 
                    + e.getStatusCode());
            return ResponseEntity.status(e.getStatusCode())
                    .contentType(MediaType.APPLICATION_JSON)
                    .body(e.getResponseBodyAsString()); 
            
        } catch (Exception e) {
            System.err.println("🔴 Error inesperado en el Proxy: " + e.getMessage());
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .contentType(MediaType.APPLICATION_JSON)
                    .body("{\"error\": \"Error interno en el servidor proxy de Spring Boot: " + e.getMessage() + "\"}");
        }
    }

    private RestClient.RequestBodySpec prepararPeticionBase(String metodo, URI uri, String token) {
        RestClient.RequestBodyUriSpec uriSpec;
        switch (metodo) {
            case "GET": uriSpec = (RestClient.RequestBodyUriSpec) restClient.get(); break;
            case "POST": uriSpec = restClient.post(); break;
            case "PUT": uriSpec = restClient.put(); break;
            case "DELETE": uriSpec = (RestClient.RequestBodyUriSpec) restClient.delete(); break;
            default: return null;
        }

        RestClient.RequestBodySpec spec = uriSpec.uri(uri).header("Accept", "application/json");
        if (token != null && !token.isBlank()) {
            spec.header("Authorization", token);
        }
        return spec;
    }
}
