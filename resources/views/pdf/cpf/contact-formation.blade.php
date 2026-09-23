@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CONTRAT D'ENSEIGNEMENT À LA CONDUITE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 14px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid black;
            font-size: 8px;
        }
        th, td {
            padding: 5px;
            vertical-align: top;
        }
        .underline {
            text-decoration: underline;
        }
        .bold {
            font-weight: bold;
        }
        .center {
            text-align: center;
        }
        .signature-table {
            width: 100%;


        }
        .signature-table td {

            width: 33%;

        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
<!-- Page 1 -->
<div style="border: 1px solid black;padding: 8px;margin-bottom: 30px">
<h1>CONTRAT D'ENSEIGNEMENT À LA CONDUITE<br>CATÉGORIE B DU PERMIS DE CONDUIRE</h1>
</div>
<p>En application des dispositions de l'article R.246.2 du Code de la route :</p>

<h2>1. LES PARTIES CONTRACTANTES :</h2>

<table border="1">
    <tr>
        <th>Entre :</th>
        <th>Et :</th>
    </tr>
    <tr>
        <td>Raison ou dénomination sociale de l'établissement : SAS PASSPERMISFACILE</td>
        <td> Mme : <b>{{ $data['test_pro']['name']}}</b></td>
    </tr>
    <tr>
        <td>Forme juridique et montant du capital social (pour les sociétés commerciales) : SA au capital de 2000€</td>
        <td>  Né(e) le : <b>{{ Carbon::parse($data['test_pro']['date_naissance'])->format('d/m/Y')}}</b></td>
    </tr>
    <tr>
        <td>  Le cas échéant, préciser la qualité : N° RCS de COMPIEGNE sous le numéro B 979 143 294</td>
        <td>   Adresse : <b>........</b></td>
    </tr>
    <tr>
        <td>  N° tél : 0970701616</td>
        <td> </td>
    </tr>
    <tr>
        <td>  Courriel : PASSPERMISFACILE@GMALL.COM</td>
        <td>   Mail : <b>{{ $data['test_pro']['email']}}</b></td>
    </tr>
    <tr>
        <td> N° d'identification à la TVA : FR40979143294</td>
        <td>  N° tél : <b>{{ $data['test_pro']['phone']}}</b></td>
    </tr>
    <tr>
        <td>  Numéro de déclaration d'activité : 76311313631</td>
        <td>  Nom du représentant légal (pour les mineurs) :</td>
    </tr>
    <tr>
        <td> Exploité par Monsieur ETIENNE FLORIAN</td>
        <td> </td>
    </tr>
    <tr>
        <td> Agréé(é) sous le numéro E2303100100</td>
        <td> </td>
    </tr>
    <tr>
        <td>  délivré par la préfecture de TOULOUSE </td>
        <td>   Adresse du représentant légal (si différent):</td>
    </tr>
    <tr>
        <td> </td>
        <td> </td>
    </tr>
    <tr>
        <td>  Ci-après désigné(e) l'école de conduite : PASSPERMISFACILE</td>
        <td>   Ci-après désigné l'élève.</td>
    </tr>

</table>

<h2>2. ÉVALUATION PRÉALABLE DE L'ÉLÈVE</h2>

<p>L'évaluation du niveau du candidat avant l'entrée en formation est obligatoire. En application de l'article L. 213-2 du code de la route, le présent contrat est conclu après une évaluation préalable du candidat, afin de déterminer le nombre prévisionnel d'heures de formation pratique à la conduite nécessaires</p>

<p class="bold">L'évaluation de l'élève a été réalisée le: <b>{{ Carbon::parse($data['created_at'])->format('d/m/Y')}}</b></p>
<p>À l'issue de cette évaluation, le nombre d'heures prévisionnel de formation pratique est de : <b>{{$data['test_pro']['nb_heur']}} </b>heures.</p>

<h2>3. L'OBJET DU CONTRAT</h2>

<p>Conformément aux articles L. 213-2 et R. 213-3 du code de la route et à l'arrêté du 22 décembre 2009 relatif à l'apprentissage de la conduite des véhicules à moteur de la catégorie B, le présent contrat a pour objet d'établir les conditions et les modalités de l'enseignement, théorique et ou pratique, de la conduite des véhicules à moteur de la catégorie B et de la sécurité routière.</p>

<h2>4. DATE DE PRISE D'EFFET ET DURÉE DU CONTRAT</h2>

<p>Le présent contrat entre en vigueur entre les parties au jour de sa signature pour une durée de 12 mois,</p>
<p class="bold">soit jusqu'au : <b>{{ Carbon::parse($data['created_at'])->addYear()->format('d/m/Y')}}</b></p>
<p>Les tarifs, les prix détaillés et les termes du contrat ne sont pas révisables pendant toute la durée du contrat sauf modification législative ou réglementaire.</p>
<p>Le contrat peut faire l'objet d'une prolongation par voie d'avenant à l'initiative de l'une ou l'autre des parties.</p>
<p>Le présent contrat porte sur une durée de un an</p>

<!-- Page Break -->


<!-- Page 2 -->
<h2>5. TARIFS DES PRESTATIONS ET PRIX DE LA FORMATION</h2>

<table>
    <tr>
        <th>Prestations CPF</th>
        <th>avec code</th>
    </tr>
@if(empty($data['offre']))
    <tr>
        <td colspan="2" class="bold">boite manuelle sans code</td>
    </tr>
    <tr>
        <td>Forfait boite manuelle 6H: 550 € sans code</td>
        <td>750 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 12 Heures: 1080 € sans code</td>
        <td>1280 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 22 Heures: 1950 € sans code</td>
        <td>2150 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 27 Heures: 2390 € sans code</td>
        <td>2590 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 31 Heures: 2830 € sans code</td>
        <td>3030 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 37 Heures: 3270 € sans code</td>
        <td>3470 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 42 Heures: 3720 € sans code</td>
        <td>3920 € avec code</td>
    </tr>

    <tr>
        <th>boite automatique sans code</th>
        <th>avec code</th>
    </tr>
    <tr>
        <td>Forfait boite automatique 7H: 750 € sans code</td>
        <td>950 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 12 Heures: 1490 € sans code</td>
        <td>1690 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 22 Heures: 2150 € sans code</td>
        <td>2350 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 27 Heures: 2490 € sans code</td>
        <td>2690 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 31 Heures: 2930 € sans code</td>
        <td>3130 € avec code</td>
    </tr>
    <tr>
        <td>Forfait accéléré 37 Heures: 3370 € sans code</td>
        <td>3570 € avec code</td>
    </tr>

    <tr>
        <td class="bold center">TOTAL TTC EN EUROS</td>
        <td>...... €</td>
    </tr>
    @else
        <tr>
            <td>{{$offre['name']}}</td>
            <td>{{$offre['price_with_code']}} €</td>

        </tr>
        <tr>
            <td class="bold ">TOTAL TTC EN EUROS</td>
            <td>{{$offre['price_with_code']}} €</td>
        </tr>
    @endif
</table>



<h2>6. PROGRAMME ET DÉROULEMENT DE LA FORMATION</h2>

<p>L'école de conduite s'engage à délivrer à l'élève une formation théorique et pratique conforme aux dispositions législatives et réglementaires en vigueur.</p>

<p>Les objectifs de la formation sont précisés dans les quatre compétences de formation du livret d'apprentissage remis à l'élève le jour de la signature du contrat. Ces compétences sont les suivantes : <span class="bold">MAÎTRISER LE MANIEMENT</span> du véhicule dans un trafic faible ou nul ; <span class="bold">APPRÉHENDER</span> la route et circuler dans des conditions normales ; <span class="bold">CIRCULER</span> dans des conditions difficiles et partager la route avec les autres usagers ; <span class="bold">PRATIQUER</span> une conduite autonome, sûre et économique.</p>

<!-- Page Break -->


<!-- Page 3 -->
<h2>7. FORMATION THÉORIQUE GÉNÉRALE (CODE DE LA ROUTE)</h2>

<h2>A. Formation théorique générale (code de la route)</h2>

<h3>A. 1. Programme de formation en vigueur</h3>

<p>La formation théorique générale dispensée par l'école de conduite correspond au programme de l'épreuve théorique générale (ETG).<br>
    Elle porte notamment sur la connaissance des règlements relatifs à la circulation et la conduite d'un véhicule, ainsi que sur celle des bons comportements du conducteur. Seront également dispensés les règles de sécurité routière à appliquer dans les tunnels, les précautions à prendre en quittant le véhicule, les facteurs de sécurité concernant le chargement du véhicule et les personnes transportées, les règles de conduite respectueuses de l'environnement, ainsi que la réglementation relative à l'obligation d'assurance et aux documents administratifs liés à l'utilisation du véhicule.</p>

<h3>A. 2. Déroulement de la formation</h3>

<p>L'enseignement théorique se déroule soit sur place, soit à distance, soit les deux, soit en cours individuel ou en cours collectif (si code intensif).</p>

<h3>A. 3. Moyens pédagogiques et techniques</h3>

<p>Si enseignement à distance : depuis smartphone, ordinateur ou tablette. Les offres sont valables 12 mois. Pour plus d'informations relatives aux outils informatiques voir les CGU sur le site internet : www.passperrmisfacile.fr<br>
    Si enseignement en salle de code : depuis une des agences agréées préfectoralement par PASSPERMISFACILE.</p>

<h3>A. 4. Accompagnement à l'épreuve théorique générale (ETG)</h3>

<p>Lorsque l'élève est convoqué à l'épreuve théorique générale de l'examen du permis de conduire, il s'y rend par ses propres moyens. L'élève devra se munir d'une <span class="bold">pièce d'identité valide</span>, à défaut il ne pourra être admis à l'examen.</p>

<h3>A. 5. Epreuve théorique générale</h3>

<p>L'épreuve théorique générale est réglementée par l'Etat. L'organisation de cette épreuve est notamment assurée par des opérateurs privés agréés par l'Etat. Le paiement des frais s'effectue directement par l'élève auprès de l'opérateur via la plateforme Lepermislibre ou par ses propres moyens sur le site de l'opérateur.</p>

<h2>B. Formation pratique (conduite)</h2>

<h3>B. 1. Programme de formation en vigueur</h3>

<p>Le programme de formation respecte le référentiel Référentiel pour l'Éducation à une Mobilité Citoyenne</p>

<h3>B. 2. Calendrier</h3>

<p>Le calendrier de formation pratique est établi par l'école de conduite en concertation avec l'élève, en fonction de leurs disponibilités respectives.</p>

<h3>B. 3. Déroulement de la formation</h3>

<p>L'enseignement pratique se déroule en cours individuel sur voie ouverte à la circulation, soit en boite manuelle ou soit en boite automatique selon le forfait choisi par l'élève. La durée de chaque leçon en formation pratique comprend le temps nécessaire notamment à l'accueil, la détermination de l'objectif, la leçon, l'évaluation et le bilan de la leçon.</p>

<h3>B. 4. Évaluation des compétences en fin de formation initiale</h3>

<p>Pendant la formation pratique définie lors de l'évaluation préalable, ou à tout moment à la demande de l'élève, l'enseignant effectue un bilan des compétences acquises par l'élève.<br>
    Si l'élève satisfait à ce bilan, l'école de conduite lui délivre une attestation de fin de formation initiale dans le cadre de la conduite accompagnée.<br>
    Dans le cas contraire, en fonction du résultat obtenu par l'élève et de son niveau, l'école de conduite précise les points à approfondir. La poursuite de la formation dans le cadre de la conduite supervisée pourra être envisagée.<br>
    Lorsque le nombre d'heures prévues initialement au contrat, n'a pas suffi à l'élève pour atteindre le niveau lui permettant de se présenter à l'épreuve pratique ou en cas d'échec à cette épreuve, un complément d'heures de formation pourra être proposé par l'école de conduite. L'élève a la possibilité d'accepter ou de refuser. En cas d'accord, un avenant au présent contrat sera signé entre les 2 parties.</p>



<!-- Page 4 -->
<h3>B. 5. Présentation à l'épreuve pratique du permis de conduite</h3>

<p>L'élève sera présenté à l'épreuve pratique par l'école de conduite, suivant les dates arrêtées et communiquées par l'autorité administrative.<br>
    En cas d'échec, et après accord entre les parties sur les besoins de l'élève, l'école de conduite présentera ce dernier à une nouvelle épreuve pratique, en fonction du calendrier qui lui est communiqué par l'autorité administrative.</p>

<h3>B. 6. Accompagnement à l'épreuve pratique</h3>

<p>Le jour de l'épreuve pratique, l'école de conduite assure l'accompagnement de l'élève sur le centre de l'examen et met à sa disposition le véhicule de l'école de conduite pendant toute la durée de l'épreuve.<br>
    Les frais d'accompagnement facturés à ce titre par l'école de conduite à l'élève correspondent à une heure de conduite, conformément aux dispositions de l'article R. 213-3-3 du code de la route.</p>

<h2>8. OBLIGATIONS DES PARTIES</h2>

<p>En cas d'annulation des leçons en formation pratique : Sauf cas de force majeure ou motif légitime dûment justifié à l'école de conduite, toute leçon non décommandée par l'élève au moins 48 heures à l'avance n'est pas remboursée. Si elle n'a pas été payée à l'avance, elle est considérée comme due. Sauf cas de force majeure ou motif légitime dûment justifié à l'élève, l'école de conduite s'engage à n'annuler aucune leçon moins de 48 heures à l'avance. À défaut la leçon doit être reportée et remboursée.</p>

<h3>A. Démarches administratives</h3>

<p>En vertu du présent contrat, l'élève peut choisir de mandater l'école de conduite pour accomplir en son nom et place toutes les démarches et formalités nécessaires auprès de l'administration, en vue de l'enregistrement de son livret et de son dossier d'examen. L'élève est avisé par l'école de conduite de la liste des documents à fournir pour constituer son dossier d'examen.<br>
    L'élève garde la possibilité de mettre fin au mandat à tout moment conformément à la loi, moyennant, le cas échéant, le paiement d'une somme compensant strictement les moyens engagés par l'école de conduite jusqu'à la résiliation.<br>
    L'école s'engage à déposer le dossier, dès lors qu'il est complet et à fournir à l'élève son numéro d'enregistrement préfectoral harmonisé (NEPH). Le mandataire ne saurait être tenu responsable du retard pris par le mandant pour fournir les pièces justificatives ou de celui imputable à l'autorité compétente pour enregistrer ou valider la demande.</p>

<h3>B. Inscription aux épreuves théorique et pratique du permis de conduire</h3>

<p>L'inscription à l'épreuve théorique générale du code de la route ou à l'épreuve pratique du permis de conduire peut être réalisée par l'élève ou par l'école de conduite. Dans ce cas, en vertu du présent contrat, l'élève peut choisir de mandater l'école de conduite pour accomplir en son nom et place toutes les démarches et formalités nécessaires auprès des organismes agréés pour l'épreuve théorique générale, et de l'administration, en vue de la réservation des places d'examen. L'élève garde la possibilité de mettre fin au mandat à tout moment conformément à la loi, moyennant, le cas échéant, le paiement d'une somme compensant strictement les moyens engagés par l'école de conduite jusqu'à la résiliation.<br>
    L'école de conduite s'engage à inscrire l'élève aux épreuves théoriques ou pratiques du permis de conduire à une date en accord avec ce dernier si il souscrit au forfait PASSPERMIS DÉCOUVERTE.<br>
    L'inscription à l'épreuve pratique est réalisée par PASSPERMISFACILE.</p>

<h2>9. OBLIGATIONS DE L'ÉLÈVE</h2>

<p>ÊTRE ÂGÉ DE 16 ANS MINIMUM OU 15 ANS MINIMUM EN CAS D'APPRENTISSAGE ANTICIPÉ DE LA CONDUITE<br>
    ÊTRE DÉTENTEUR, NOTAMMENT LORS DES LEÇONS PRATIQUES, DES DOCUMENTS SUIVANTS : LIVRET D'APPRENTISSAGE CONFORME À LA RÈGLEMENTATION ; FORMULAIRE DE LA DEMANDE DE PERMIS DE CONDUIRE VALIDÉE PAR LE PRÉFET DU LIEU DE DÉPARTEMENT DE SON DÉPÔT.</p>

<h2>10. OBLIGATIONS DE L'ÉCOLE DE CONDUITE</h2>

<p>DÉLIVRER À L'ÉLÈVE UNE FORMATION THÉORIQUE ET PRATIQUE CONFORME AUX PROGRAMMES EN VIGUEUR.<br>
    PRÉSENTER LE CANDIDAT À L'ÉPREUVE OU AUX ÉPREUVES EN FOURNISSANT LES MOYENS NÉCESSAIRES SAUF SI LE CANDIDAT SOUHAITE SE PRÉSENTER DIRECTEMENT.</p>

<!-- Page Break -->
<div class="page-break"></div>

<!-- Page 5 -->
<h2>11. Modalités de paiement</h2>

<p>Le paiement s’effectue en partie ou totalement avec le compte professionnel de formation de l’élève selon le forfait dit « CPF » de notre auto-école. Si l’élève devait compléter un reste à charge pour payer la totalité de son forfait alors celui ci s’effectue exclusivement par carte bancaire.</p>

<h2>12. Conditions de rétractation ou de résiliation</h2>

<h3>A. Rétractation</h3>

<p>Dans le cadre d'un contrat conclu à distance tel que défini à l'article L. 221-1 du code de la consommation, l'élève bénéficie, à compter de la date de la signature du présent contrat, d'un droit de rétraction de 14 jours conformément à l'article L. 221-18 du même code. Dans l'hypothèse où l'élève souhaite exercer ce droit, il adresse sa décision de se rétracter à l'école de conduite soit par lettre recommandée ou envoi recommandé électronique avec avis de réception à l'adresse postale de l'école de conduite ou par courriel à l'adresse électronique de l'école de conduite. Le formulaire de rétractation figurant en annexe peut être utilisé par l'élève. Si l'élève a expressément demandé à débuter sa formation avant l'expiration du délai de rétractation, l'école de conduite lui facturera le montant des prestations réalisées jusqu'à la notification par l'élève de sa décision de se rétracter. En cas de prestations déjà réglées par l'élève dans le cadre d'un forfait, le remboursement s'effectue au prorata des prestations déjà réalisées. En cas de prestations non encore facturées à l'élève dans le cadre d'un forfait, la facturation s'effectue au prorata des prestations déjà réalisées.</p>

<h3>2. Résiliation</h3>

<p>L'élève peut résilier le présent contrat à tout moment par lettre recommandée ou envoi recommandé électronique avec avis de réception à l'adresse postale de l'école de conduite ou par courriel à l'adresse électronique de l'école de conduite, moyennant paiement des prestations déjà réalisées. La résiliation prend effet 15 jours à compter de la date de première présentation de la lettre recommandée ou de l'envoi recommandé électronique. Ce délai de préavis ne s'applique pas en cas de motif légitime.<br>
    L'école de conduite peut résilier le présent contrat en cas de violence avérée, de mise en danger d'autrui, d'incivilités ou de manquements répétés à l'une de ses obligations issues du présent contrat (hypothèse : retards de paiement non régularisés), après mise en demeure spécifiant le motif de la résiliation notifiée par lettre recommandée ou de l'envoi recommandé électronique avec avis de réception. La résiliation prend effet 15 jours à compter de la date de première présentation de la lettre recommandée ou de l'envoi recommandé électronique. L'élève peut contester la décision de l'école de conduite. A défaut de solution, il peut recourir à une procédure de médiation. La résiliation du présent contrat avant son terme entraîne l'apparement définitif des comptes. L'école de conduite facturera le montant des prestations réalisées jusqu'à la date de la prise d'effet de la résiliation. En cas de prestations déjà réglées par l'élève dans le cadre d'un forfait, le remboursement s'effectue au prorata des prestations déjà réalisées. En cas de prestations non encore facturées à l'élève dans le cadre d'un forfait, la facturation s'effectue au prorata des prestations déjà réalisées. Le dossier de l'élève lui sera restitué gratuitement à tout moment à sa demande ou à celle d'un tiers dûment mandaté par lui. En cas de résiliation sans motif légitime, en dehors de paiement par arrhes, avant tout commencement de la formation pratique, l'école de conduite pourra retenir une somme correspondant au montant des frais liés exclusivement à la résiliation, dûment prévue au présent contrat (article III) et dûment justifiée. Le présent contrat est résilié de plein droit en cas de retrait de l'agrément de l'école de conduite. L'école de conduite rembourse sans délai l'élève de toutes sommes payées par lui n'ayant donné lieu à prestation.</p>



<!-- Page 6 -->
<h2>13. - Souscription par l'établissement</h2>

<p>L'école de conduite est titulaire d'un contrat d'assurance de responsabilité civile garantissant ses véhicules et couvrant les dommages pouvant être causés aux tiers ainsi qu'aux personnes se trouvant à l'intérieur des véhicules pendant la formation ou lors des examens pratiques dans les conditions prévues à l'article L.211-1 du code des assurances, souscrit auprès de AXA ASSURANCE (14 Av. du Général de Gaulle, 78490 Montfort-l'Amaury)</p>

<h2>14. - Règlement des litiges</h2>

<p>En cas de désaccord ou litige entre les parties, le présent contrat est soumis au droit français.<br>
    Tout litige découlant de la validité, exécution, résiliation du présent contrat est soumis aux tribunaux compétents dans les conditions de droit commun.<br>
    A défaut de solution amiable, l'élève peut recourir gratuitement, dans les conditions prévues aux articles L. 612-1 et suivants et R. 612-1 et suivants du code de la consommation à un médiateur de la consommation en vue de la résolution amiable de tout litige l'opposant à l'école de conduite, relatif au présent contrat :<br>
    ANM Conso – 2, rue de Colmar 94300 VINCENNES - contact@anm-conso.com - 01 58 64 00 05 - www.anm-conso.com. Avant de saisir le médiateur, l'élève doit avoir adressé au préalable une réclamation écrite à l'école de conduite. Il doit saisir le médiateur dans le délai d'un an maximum à compter de sa réclamation écrite.</p>

<h2>15. - Protection des données personnelles</h2>

<p>L'élève est informé que les données personnelles recueillies sur ce contrat font l'objet de traitements automatisés nécessaires à l'exécution de ce dernier. L'école de conduite est responsable du traitement de ces données personnelles qu'elle collecte et traite pour établir le contrat et fournir les services d'enseignement à la conduite qui y sont mentionnés. Seules les données personnelles strictement nécessaires à l'exécution du présent contrat sont traitées par l'école de conduite. Elles sont obligatoires : à défaut la fourniture des services d'apprentissage à la conduite pourrait être suspendue. Elles ne font l'objet d'aucun transfert ni communication à des tiers sauf obligations législatives ou réglementaires. Dans le cas où l'élève a mandaté l'école de conduite pour effectuer les formalités nécessaires à l'inscription à l'épreuve théorique générale (code) ou à l'examen de la conduite, ainsi qu'à l'établissement de son permis de conduire, l'école de conduite transmettra aux opérateurs responsables les données personnelles strictement nécessaires à l'exécution de ces formalités. L'école de conduite s'engage à conclure avec ses soustraitants un contrat de traitement de données personnelles conforme à l'article 28 du règlement n° 2016/679, dit règlement général sur la protection des données (RGPD). L'école de conduite s'engage à mettre en œuvre les mesures techniques et organisationnelles appropriées afin de garantir un niveau de sécurité optimal des données personnelles qu'il traite. Les données recueillies seront conservées pendant toute la durée du contrat et seront supprimées au bout de 5 ans à compter de son terme. Si l'élève ne souhaite pas que ses données soient utilisées par les partenaires de l'école de conduite à des fins de prospection, il enverra un mail à l'adresse suivante: contact@passpermisfacile.fr<br>
    L'élève bénéficie d'un droit d'accès, de portabilité, de rectification, d'effacement de ses données personnelles, ainsi qu'un droit de limitation ou d'opposition au traitement de celles-ci. Il peut exercer ses droits en s'adressant à passpermisfacile. Il a le droit d'introduire une réclamation auprès de la Commission nationale de l'informatique et des libertés (CNIL).<br>
    Opposition au démarchage téléphonique - En tant que consommateur, si l'élève ne souhaite pas faire l'objet de prospection commerciale par voie téléphonique, il est informé de son droit de s'inscrire gratuitement sur la liste d'opposition au démarchage téléphonique Bloctel sur le site internet :<br>
    http://www.bloctel.gouv.fr ou par courrier Société Opposetel - Service Bloctel, 6, rue Nicolas-Siret, 10000 Troyes.</p>

<p class="bold" style="margin-top: 40px;margin-bottom: 40px">Fait à Toulouse le : <b>{{ Carbon::parse($data['created_at'])->format('d/m/Y')}}</b> en deux exemplaires originaux</p>

<table  class="signature-table">
    <tr>
        <td class="center">Signature de l'élève</td>
        <td class="center">Signature du représentant légal de l'élève mineur, le cas échéant</td>
        <td class="center">Signature du responsable de l'école de conduite et cachet</td>
    </tr>
    <tr>
        <td style="height: 40px" class="center">
            <b>  {{ $data['test_pro']['name']}}</b>
            <img style="margin-top: -10px;z-index: 10000" src="{{$data['test_pro']['signature']}}" width="120" height="40">
        </td>
        <td class="center"></td>
        <td class="center">
           <b> ETIENNE FLORIAN gérant de l’auto-école PASSPERMISFACILE</b>
            <img style="margin-top: -10px;z-index: 10000"  src="{{public_path('assets/signature.png')}}" width="120" height="40">
        </td>
    </tr>
</table>
</body>
</html>
