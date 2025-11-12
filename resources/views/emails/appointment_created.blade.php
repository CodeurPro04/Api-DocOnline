<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Rendez-vous | Meetmedpro</title>
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
            background: #2563eb;
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
            color: #2563eb;
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
            border-bottom: 2px solid #2563eb;
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
            background: #fef3c7;
            color: #92400e;
            border-radius: 4px; 
            font-size: 13px; 
            font-weight: 600;
            margin-top: 16px;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
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
            color: #2563eb;
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
            <h1>
                @if($recipientType === 'patient')
                Votre demande est enregistrée
                @else
                Nouvelle demande de rendez-vous
                @endif
            </h1>
            <p class="header-subtitle">
                @if($recipientType === 'patient')
                Votre santé, notre priorité
                @else
                Gestion simplifiée de votre agenda
                @endif
            </p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                @if($recipientType === 'patient')
                <p>Bonjour <strong>{{ $patient->prenom }} {{ $patient->nom }}</strong>,</p>
                <p>Votre demande de rendez-vous a été enregistrée avec succès et est en attente de confirmation par le médecin.</p>
                @else
                <p>Docteur <strong>{{ $medecin->prenom }} {{ $medecin->nom }}</strong>,</p>
                <p>Vous avez reçu une nouvelle demande de rendez-vous de la part d'un patient via la plateforme Meetmedpro.</p>
                @endif
            </div>

            <!-- Appointment Details -->
            <div class="appointment-card">
                <h3 class="card-title">Détails du rendez-vous</h3>
                
                <div class="info-row">
                    <span class="label">Patient : </span>
                    <span class="value"> {{ $patient->prenom }} {{ $patient->nom }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Médecin : </span>
                    <span class="value"> Dr {{ $medecin->prenom }} {{ $medecin->nom }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Date : </span>
                    <span class="value"> {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Heure : </span>
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
                
                <div class="status-badge">⏳ En attente de confirmation</div>
            </div>

            <!-- Next Steps -->
            <div class="info-box">
                <div class="info-box-title">
                    @if($recipientType === 'patient')
                    Prochaines étapes
                    @else
                    Action requise
                    @endif
                </div>
                <ul>
                    @if($recipientType === 'patient')
                    <li>Le médecin examinera votre demande sous 24-48 heures</li>
                    <li>Vous recevrez une confirmation ou un refus par email</li>
                    <li>En cas de confirmation, le rendez-vous sera définitivement planifié</li>
                    @else
                    <li>Veuillez confirmer ou refuser ce rendez-vous dans les 48 heures</li>
                    <li>Le patient sera automatiquement notifié de votre décision</li>
                    <li>Utilisez votre espace médecin pour gérer votre agenda</li>
                    @endif
                </ul>
            </div>
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