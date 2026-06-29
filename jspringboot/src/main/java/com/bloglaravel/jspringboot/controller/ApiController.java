package com.bloglaravel.jspringboot.controller;

import com.bloglaravel.jspringboot.service.ApiService;
import jakarta.servlet.http.HttpServletRequest;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api") 
public class ApiController {

    private final ApiService apiService;

    public ApiController(ApiService apiService) {
        this.apiService = apiService;
    }

    @RequestMapping(
        value = "/**", 
        method = {RequestMethod.GET, RequestMethod.POST, RequestMethod.PUT, RequestMethod.DELETE}
    ) 
    public ResponseEntity<String> espejoLaravel(
            @RequestHeader(value = "Authorization", required = false) String token,
            @RequestBody(required = false) Object body,
            HttpServletRequest request) {

        String method = request.getMethod().toUpperCase().trim();         
        String uriCompleta = request.getRequestURI(); 
        
        String endpointClean = uriCompleta.substring(request.getContextPath().length() + "/api".length());
        
        if (!endpointClean.startsWith("/")) {
            endpointClean = "/" + endpointClean;
        }

        String query = request.getQueryString();
        if (query != null && !query.isEmpty()) {
            endpointClean += "?" + query;
        }

        return apiService.procesarPeticion(method, endpointClean, token, body);
    }
}
