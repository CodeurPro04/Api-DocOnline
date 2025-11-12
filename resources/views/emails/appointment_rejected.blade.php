<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous Refusé | Meetmedpro</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6; 
            color: #333333; 
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #ffffff; 
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .header { 
            background: #ef4444;
            padding: 32px 40px;
            text-align: center; 
            color: white; 
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .header h1 {
            font-size: 22px;
            font-weight: 600;
            margin-top: 16px;
        }
        
        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 4px;
        }
        
        .content { 
            padding: 40px; 
        }
        
        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 24px;
        }
        
        .greeting strong {
            color: #ef4444;
        }
        
        .appointment-card { 
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 24px;
            margin: 24px 0;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #ef4444;
        }
        
        .info-row { 
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label { 
            font-size: 14px; 
            color: #6b7280; 
            font-weight: 500;
        }
        
        .value { 
            font-size: 14px; 
            color: #111827; 
            font-weight: 600;
            text-align: right;
        }
        
        .status-badge { 
            display: inline-block;
            padding: 8px 16px; 
            background: #fee2e2;
            color: #991b1b;
            border-radius: 4px; 
            font-size: 13px; 
            font-weight: 600;
            margin-top: 16px;
        }
        
        .reason-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 4px;
            padding: 16px;
            margin: 24px 0;
        }
        
        .reason-box-title {
            font-size: 15px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 8px;
        }
        
        .reason-box p {
            font-size: 14px;
            color: #991b1b;
            font-style: italic;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
            padding: 16px;
            margin: 24px 0;
        }
        
        .info-box-title {
            font-size: 15px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }
        
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        
        .info-box li {
            padding: 6px 0;
            font-size: 14px;
            color: #1e40af;
            padding-left: 20px;
            position: relative;
        }
        
        .info-box li::before {
            content: '•';
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        .button-container {
            text-align: center;
            margin: 24px 0;
        }
        
        .button {
            display: inline-block;
            padding: 12px 28px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .button:hover {
            background: #4b5563;
        }
        
        .note {
            font-size: 14px;
            color: #6b7280;
            margin-top: 24px;
        }
        
        .footer { 
            background: #f9fafb;
            padding: 32px 40px; 
            text-align: center; 
            color: #6b7280; 
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-logo {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .footer p {
            margin: 8px 0;
        }
        
        .footer-links {
            margin: 16px 0;
        }
        
        .footer-link {
            color: #ef4444;
            text-decoration: none;
            margin: 0 12px;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 0;
            }
            
            .content { 
                padding: 24px 20px; 
            }
            
            .header {
                padding: 24px 20px;
            }
            
            .appointment-card {
                padding: 20px;
            }
            
            .footer {
                padding: 24px 20px;
            }
            
            .info-row {
                flex-direction: column;
                gap: 4px;
            }
            
            .value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Meetmedpro</div>
            <h1>Rendez-vous Refusé ❌</h1>
            <p class="header-subtitle">Demande non acceptée</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                <p>Bonjour <strong>{{ $patient->prenom }} {{ $patient->nom }}</strong>,</p>
                <p>Nous regrettons de vous informer que votre demande de rendez-vous n'a pas pu être acceptée par le Dr {{ $medecin->prenom }} {{ $medecin->nom }}.</p>
            </div>

            <!-- Appointment Details -->
            <div class="appointment-card">
                <h3 class="card-title">Détails de la demande refusée</h3>
                
                <div class="info-row">
                    <span class="label">Patient : </span>
                    <span class="value"> {{ $patient->prenom }} {{ $patient->nom }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Médecin : </span>
                    <span class="value"> Dr {{ $medecin->prenom }} {{ $medecin->nom }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Date demandée : </span>
                    <span class="value"> {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Heure demandée : </span>
                    <span class="value"> {{ $appointment->time }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Type de consultation : </span>
                    <span class="value"> {{ $appointment->consultation_type }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Référence : </span>
                    <span class="value"> #RDV{{ str_pad($appointment->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                
                <div class="status-badge">❌ Refusé</div>
            </div>

            <!-- Alternative -->
            <div class="info-box">
                <div class="info-box-title">Que faire maintenant ?</div>
                <ul>
                    <li>Choisir une autre date ou créneau horaire</li>
                    <li>Consulter un autre médecin de la même spécialité</li>
                    <li>Nous contacter pour plus d'informations</li>
                </ul>
            </div>

            <!-- Note -->
            <p class="note">
                Nous sommes désolés pour ce contretemps et espérons pouvoir vous accompagner prochainement.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">Meetmedpro</div>
            <p>Votre plateforme de santé connectée</p>
            
            <div class="footer-links">
                <a href="#" class="footer-link">Mentions légales</a>
                <a href="#" class="footer-link">Confidentialité</a>
                <a href="#" class="footer-link">Contact</a>
                <a href="#" class="footer-link">Aide</a>
            </div>
            
            <p>© {{ date('Y') }} Meetmedpro. Tous droits réservés.</p>
            <p style="margin-top: 8px; opacity: 0.7;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
            </p>
        </div>
    </div>
</body>
</html>