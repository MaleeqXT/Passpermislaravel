<script setup lang="ts">
import { dateFormat } from '@shared/utils';

defineProps({
    studentContract: Object,
});
</script>

<template>
    <div v-if="studentContract" class="divide-y">
        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <h1 class="text-2xl font-bold text-center mb-4">Contrat de Formation Permis B</h1>
            <article class="mb-5">
                <p class="mb-5"><strong>Entre, d'une part :</strong></p>
                <p>
                    EasyMonitor ,société à responsabilité limitée au capital de 10 000 euros, dont le siège social se situe au 22 RUE
                    CHARLES LESCOT, 60700
                </p>
                <p class="mb-5">
                    PONT-SAINTE-MAXENCE, immatriculée au R.C.S. de Beauvais sous le n° 883 546 525, disposant d'un établissement au 22 rue
                    charles lescot pont sainte maxence et autorisée à exploiter, sous le numéro E 23 060 0017 0, un établissement
                    d'enseignement, à titre onéreux, de la conduite des véhicules à moteur et de la sécurité routière selon arrêté délivré
                    le 01 août 2023.
                </p>

                <p class="mb-5">Ci-après dénommée <strong>« l'Établissement »</strong></p>
            </article>

            <article class="mb-5">
                <p class="mb-5">Et d'autre part :</p>
                <ul class="mb-5 font-bold space-y-1.5">
                    <li>
                        Nom et prénoms :
                        <span class="text-primary">{{ studentContract.training?.student?.user?.name }}</span>
                    </li>

                    <li>
                        Numéro NEPH : <span class="text-primary">{{ studentContract.training?.student?.neph }}</span>
                    </li>

                    <li>
                        Domicilié(e) à :<span class="text-primary">{{ studentContract.training?.student?.user?.adresse }}</span>
                    </li>
                    <li>
                        CP / Ville :
                        <span class="text-primary"
                            >{{ studentContract.training?.student?.user?.postal }}
                            {{ studentContract.training?.student?.user?.ville }}</span
                        >
                    </li>
                    <li>Lieu de naissance : <span class="text-primary">France</span></li>
                </ul>
                <p class="mb-5">Ci-après dénommé <strong>« l'Élève »</strong></p>
                <p class="mb-5">Il est convenu ce qui suit :</p>
            </article>

            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 1 - OBJET DU CONTRAT</h2>
                <p class="mb-5">
                    Le présent contrat a pour objet la formation de l'Elève à l'apprentissage de la conduite et de la sécurité routière afin
                    : qu'il puisse se présenter aux examens théoriques et pratiques du permis de conduire pour le véhicule de catégorie B
                    qu'il puisse bénéficier d'une remise à niveau s'il dispose déjà dudit permis. L'Établissement s'engage à proposer à
                    l'Élève des heures de conduite dispensées par des enseignants diplômés du brevet pour l'exercice de la profession
                    d'enseignant de la conduite automobile et de la sécurité routière (BEPECASER) ou Titre Professionnel E.C.S.R (Enseignant
                    de la Conduite et de la Sécurité Routière) suivant sa demande, pour amener ce même Élève au niveau requis pour être
                    autonome et sûr. Il est conclu conformément à l'article R. 213-3 du Code de la Route issu du Décret n° 2016-381 du 30
                    mars 2016.
                </p>
            </article>
        </section>
        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />

            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 2 - EVALUATION DU NIVEAU DE L'ÉLÈVE</h2>
                <p class="mb-5">
                    L'évaluation du niveau du candidat avant l'entrée en formation est obligatoire. En application de l'article L. 213-2 du
                    code de la route, le présent contrat est conclu après une évaluation préalable du candidat dans le véhicule ou dans les
                    locaux de l'école de conduite, afin de déterminer le nombre prévisionnel d'heures de formation pratique et / ou
                    théorique à la conduite nécessaires. L'évaluation de l'élève a été réalisée par l'enseignant partenaire de l'auto-école
                    (ci-après l' « Enseignant »),
                    <br />
                    le :
                    <strong class="text-primary">
                        {{ dateFormat(studentContract.training?.student?.created_at, 'fr') }}
                    </strong>
                    à <strong class="text-primary">{{ studentContract.lieu?.name }}</strong
                    >.
                    <br />
                    Elle a donné lieu à l'élaboration d'une fiche d'évaluation. À l'issue de cette évaluation, le nombre d'heures
                    prévisionnel de formation pratique en boîte manuelle est de:
                    <strong class="text-primary"> {{ studentContract.review_monitor?.estimation }} heures</strong>.
                </p>
            </article>

            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 3 - DURÉE, RÉSILIATION DU CONTRAT</h2>
                <p class="mb-5">
                    Le présent contrat entre en vigueur entre les parties au jour de la signature du contrat et se poursuivra pour une durée
                    de douze (12) mois à compter de la date d'achat de sa dernière séance, soit jusqu'au :
                    <strong class="text-primary"> {{ dateFormat(studentContract.training?.student?.created_at, 'fr') }} </strong>. <br />
                    Les paiements effectués pour des prestations qui n'auraient pas été accomplies à l'issue du contrat, faute par l'élève
                    de réserver des cours de conduite, resteront acquis à l'établissement sauf motif légitime de l'élève. Toutefois, ce
                    contrat pourra être résilié de façon anticipée (i) par l'Élève en cas de motif légitime et (ii) par l'établissement en
                    cas de comportement de l'élève inapproprié à l'enseignement de la conduite empêchant la poursuite du contrat. La partie
                    qui souhaite résilier le contrat de façon anticipée dans les cas ci-dessus, avant l'expiration du délai de douze mois,
                    adressera à l'autre une lettre recommandée avec accusé de réception spécifiant le motif de la résiliation laquelle
                    prendra effet quinze jours après réception de cette notification. En outre, ce contrat sera résilié de plein droit sans
                    indemnité de part ou d'autre dans le cas de retrait de l'agrément de l'établissement par l'autorité préfectorale. A
                    l'issue du contrat, le dossier, qui est la propriété de l'élève lui sera personnellement restitué à sa demande, à
                    lui-même ou à une tierce personne dûment mandatée par lui.
                </p>
            </article>

            <article>
                <h2 class="bloc-title mb-5">ARTICLE 4 - PROGRAMME ET DÉROULEMENT DE LA FORMATION</h2>
                <p>
                    L'Établissement délivre une formation conforme aux objectifs contenus dans le Référentiel pour l'Education à une
                    Mobilité Citoyenne (REMC) et énumérés dans les étapes de formation du livret d'apprentissage telles que décrites en
                    annexe 1 au présent contrat et- conformes à l'arrêté du 29 juillet 2013 et ses annexes. Le calendrier prévisionnel des
                    séances de formation est établi par l'établissement en concertation avec l'Elève et lui est communiqué. Chaque séance
                    donne lieu à une évaluation et à l'inscription des indications requises sur le livret d'apprentissage. L'établissement
                    tient l'élève informée de la Progrès de sa formation. A titre d'information et sans que la décomposition ci-dessous ait
                    quelconque valeur contractuelle, une heure de conduite en circulation se décompose généralement de la façon suivante :
                </p>
            </article>
        </section>

        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <article class="mb-5">
                à 5 minutes : définition des objectifs en se référant au livret d‘apprentissage -à 45 à 50 minutes : conduite effective pour
                travailler les objectifs définis et évaluer les apprentissages -à 5 à 10 minutes : bilan et commentaires pédagogiques
                incluant la validation des objectifs et les annotations sur le livret d'apprentissage. La durée d'une séance de conduite au
                volant pour un élève ne peut excéder deux heures consécutives, l'interruption entre deux séances de conduite devant être au
                moins égale à la durée de la séance précédente.
            </article>

            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 5 - MOYENS PÉDAGOGIQUES ET TECHNIQUES</h2>
                <p class="mb-5">
                    L'Établissement mettra en œuvre toutes les compétences et moyens nécessaires pour que l'élève atteigne le niveau de
                    performance requis. La formation théorique dispensée dans l'Établissement et les cours pratiques seront exclusivement
                    dispensés par des personnes titulaires du brevet pour l'exercice de la profession d'enseignant de la conduite automobile
                    et de la sécurité routière (BEPECASER) ou du Titre Professionnel E.C.S.R (Enseignant de la Conduite et de la Sécurité
                    Routière) et d'une autorisation d'enseigner en cours de validité correspondant à la catégorie enseignée. Les véhicules
                    utilisés seront conformes à la réglementation en vigueur.
                </p>
            </article>
            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 6 - OBLIGATION DES PARTIES</h2>
                <h3 class="font-bold mb-5">Article 6.1 : Obligations de l'Établissement</h3>
                <p class="mb-5">
                    L'Établissement s'engage à dispenser la formation A ce titre, il s'engage à mettre en œuvre toutes les compétences et
                    moyens nécessaires pour que l'élève atteigne le niveau de performance requis.
                </p>
                <h3 class="font-bold mb-5">Article 6.2 : Obligations de l'Élève</h3>
                <p class="mb-5">
                    L'Elève s'engage à respecter les prescriptions pédagogiques et le calendrier de formation. L'Elève s'engage à informer
                    l'Etablissement de sa convocation à l'épreuve pratique au minimum dix (10) jours avant la date de l'examen.
                </p>
            </article>
            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 7 - ANNULATION DES LEÇONS OU EXAMENS</h2>

                <p class="mb-5">
                    L'Elève peut librement annuler un cours de conduite et se voir recréditer le compte qu'il a créé sur le site internet
                    www.passpermisfacile.fr d'autant de crédits que d'heures annulées si et seulement s'il notifie cette annulation via
                    l'application disponible sur le site internet www.passpermisfacile.fr . Tout cours de conduite non annulé sur le site
                    internet www.passpermisfacile.fr , quarante-huit (48) heures avant l'heure prévue pour le commencement du cours de
                    conduite, sera considéré comme dû et
                </p>
            </article>
        </section>

        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <article class="mb-5">
                ne sera pas recrédité sur le compte que l'Elève a créé sur le site internet www.passpermisfacile.fr sauf motif légitime
                dûment justifié. L'Établissement se réserve la possibilité d'annuler des cours ou séances sans préavis en cas de motif
                légitime dûment justifié. L'Élève sera prévenu au plus tôt par tous les moyens de l'établissement. Dans tous les cas, les
                séances déjà réglées donneront lieu à un report ou à un recrédit du compte que l'Elève a créé sur le site internet
                www.passpermisfacile.fr ou à un remboursement au choix de l'Élève. Si un élève décide de ne pas se présenter à une épreuve,
                il devra en avertir l'établissement (sauf motif légitime dûment justifié) au minimum dix (10) jours à l'avance sous peine de
                perdre les frais afférents à cette prestation.
            </article>
            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 8 - TARIFS DES PRESTATIONS ET ACCESSOIRES</h2>
                <p class="mb-5">
                    La conclusion du présent contrat emporte le droit de réserver et recevoir des prestations de formations et autres
                    prestations accessoires via le site www.passpermisfacile.fr Lesdites prestations au titre du présent contrat seront
                    soumises à la tarification suivante : Cours de conduite (par tranche d'une heure non divisible) :
                </p>
                <ul class="space-y-7 mb-5">
                    <li>
                        <p class="title-underline">FORFAIT INSCRIPTION</p>
                        <span class="ml-10">39.90€ </span>
                    </li>
                    <li>
                        <p class="title-underline">FORFAIT CODE DE LA ROUTE</p>
                        <span class="ml-10">29.90€ </span>
                    </li>

                    <li>
                        <p class="title-underline mb-7">FORFAIT MANUELLE</p>
                        <div class="font-bold">
                            <p>-1h d'évaluation : 35€</p>
                            <p>-Forfait 5h : 230€</p>
                            <p>-Forfait 10h : 450€ (possibilité en 2X sans frais)</p>
                            <p>-Forfait 20h : 790€ soit 35h (possibilité en 2X sans frais)</p>
                        </div>
                        <p>
                            (Un nombre d'heures supplémentaires peuvent être ajoutées à la fin de la session si le moniteur juge selon lui
                            que pour la sécurité de l'élève cela est indispensable. Dans ce cas là, l'élève sera invité à prendre un forfait
                            de 5h ou 10h ou 20 ou 30h si besoin. Aussitôt la fin du forfait supplémentaire fini, il pourra recevoir alors
                            son attestation de fin de formation initiale).
                        </p>
                        <p class="font-bold">-Forfait Supervisé..190€*</p>
                        <p>
                            (Un nombre d'heures supplémentaires peuvent être ajoutées à la fin de la session si le moniteur juge selon lui
                            que pour la sécurité de l'élève cela est indispensable. Dans ce cas là, l'élève sera invité à prendre un forfait
                            de 5h ou 10h ou 20 ou 30h si besoin. Aussitôt la fin du forfait supplémentaire fini, il pourra recevoir alors
                            son attestation de fin de formation initiale).
                        </p>
                    </li>
                    <li>
                        <p class="title-underline mb-7">FORFAIT AUTOMATIQUE</p>
                        <div class="font-bold">
                            <p>-1H D'évaluation : 45€</p>
                        </div>
                    </li>
                </ul>
            </article>
        </section>

        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <ul>
                <li>
                    <p>-Forfait 5h : 249€</p>
                    <p>-Forfait 13h : 640€ soit 45€/h (possibilité en 2X sans frais)</p>
                </li>
                <li>
                    <strong class="title-underline mb-7 mt-12">FORFAIT EXAMEN</strong>
                    <p class="font-bold mb-7">-PASS EXPRESS. 249€ (1h de conduite +1 place d'examen)</p>

                    <p>
                        Les prestations effectuées au titre du présent contrat seront facturées et payées à l'avance, au moment de la
                        commande via l'application disponible sur le site www.passpermisfacile.fr , conformément aux Conditions Générales de
                        Vente du site précité. Toute somme payée par avance par l'Élève à l'Établissement aura la nature d'acompte.
                    </p>
                </li>
            </ul>
            <article class="mt-5">
                <h2 class="bloc-title mb-5">ARTICLE 9 - ASSURANCE ET GARANTIE FINANCIÈRE</h2>
                <p class="mb-5">
                    L'Etablissement est titulaire d'une police d'assurance souscrite auprès de AXA COMBES ( 14 Av. du Général de Gaulle,
                    78490 Montfort-l'Amaury) conformément à l'article L 211-1 du Code des Assurances. L'Établissement n'a pas souscrit de
                    garantie financière permettant le remboursement à l'Élève des sommes trop perçues en cas de défaillance. L'Elève
                    reconnaît être informé de cette absence de garantie financière et l'accepte.
                </p>
            </article>
            <article class="mb-5">
                <h2 class="bloc-title mb-5">ARTICLE 10 - Heure d'évaluation</h2>
                <p>
                    L'évaluation de conduite est rendue obligatoire par la l'arrêté du 5 mars 1991 et étendue par le décret du 26 décembre
                    2000 qui impose au contrat de formation de contenir : "l'évaluation de niveau du candidat à l'entrée en formation,
                    notamment le nombre prévisionnel d'heures de formation". Elle doit impérativement être effectuée avant la signature d'un
                    contrat avec une école de conduite. C'est l'occasion pour le moniteur d'évaluer le niveau de son élève, mais aussi de
                    déterminer le nombre d'heures de conduite qu'il sera, à première vue, nécessaire d'effectuer avant qu'il ne puisse se
                    présenter à connaissance avec son moniteur, d'avoir une première opinion sur l'entente avec celui-ci.
                </p>
            </article>
        </section>
        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <article class="mb-5">
                <p>
                    Ceci étant dit, on entend par heure d'évaluation, toute première heure dans notre auto-école, et ce quand bien même un
                    élève prendrait plusieurs forfait.
                </p>
                <p class="my-5">exemple:</p>
                <p>
                    À l'issue de l'heure d'évaluation, un élève est estimé à 35h. Celui-ci décide dans un premier temps de prendre un
                    forfait 20h. Dès lors, il ne lui sera pas demandé de faire une autre heure d'évaluation pour son deuxième forfait 10h et
                    le dernier de 5h. La première heure d'évaluation faisant foi du nombre d'heures estimées dans sa globalité.
                </p>
                <p>
                    Aussitôt l'heure d'évaluation accomplie, l'élève sera invité sur son espace personnel en ligne a accepté cette
                    estimation. En effet, une fenêtre (pop-up) s'ouvrira pour que celui-ci coche la case “ACCEPTÉ”, ET UN RAPPEL DE
                    L'OBLIGATION DE L'ÉLÈVE VIS À VIS DE L'HEURE D'ÉVALUATION SERA PRÉSENT VIA UN LIEN HYPERTEXTE.
                </p>
                <p>Néanmoins, ceci ne l'engage nullement car cela relève de l'estimation.</p>
                <p class="mb-5">
                    Toutefois, EasyMonitor se laisse le droit d'accepter ou non d'accompagner un élève à l'examen, si le niveau de celui-ci
                    n'est pas approprié au référentiel REMC. Le ou les moniteurs ayant suivi la formation de l'élève sont dans le droit de
                    Refusé son accompagnement à l'examen.
                </p>
                <p>
                    L'acceptation de ce contrat oblige l'élève à le respecter. De plus, si l'élève pour un forfait choisi directement sur
                    son espace boutique consomme sa deuxième heure, cela sera équivalent à l'acceptation de ce contrat.
                </p>

                <ul class="grid grid-cols-2 gap-x-5 gap-y-20">
                    <li>
                        <p class="title-underline">L'Établissement :</p>
                        <p>(signature et cachet)</p>
                    </li>
                    <li>
                        <p class="title-underline">L'Élève ou son représentant légal :</p>
                        <p>(signature)</p>
                    </li>
                    <li>
                        <span class="uppercase">À , PONT SAINTE MAXENCE :</span>
                    </li>
                    <li>
                        <span class="">À ,</span>
                    </li>
                    <li>
                        le
                        <strong class="text-primary">{{ dateFormat(studentContract.training?.created_at, 'fr') }}</strong>
                    </li>
                    <li>le</li>
                </ul>
                <img src="/assets/images/common/contract.png" class="w-80 mt-1 block" alt="contract" />
            </article>
        </section>
        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <article class="mb-5">
                <h2 class="bloc-title mb-5">Informations concernant l'exercice du droit de rétractation</h2>
                <h2 class="text-2xl font-bold mb-5">Droit de rétractation :</h2>

                <p class="mb-5">
                    Conformément aux articles L. 121-21 et suivants du Code de la consommation, Vous disposez d'un délai de quatorze (14)
                    jours calendaires pour exercer votre droit de rétractation, sans avoir à Justifié de motif ni à supporter de pénalités.
                    Pour exercer le droit de rétractation, Vous devez notifier , votre décision de rétractation au moyen d'une déclaration
                    dénuée d'ambiguïté adressée par email à l'adresse suivante : <br />
                    passpermisfacilepermis@gmail.com ou par courrier à l'adresse suivante : EasyMonitor ,société à responsabilité limitée au
                    capital de 10 000 euros, dont le siège social se situe au 22 RUE CHARLES LESCOT, 60700 PONT-SAINTE-MAXENCE. Vous pouvez,
                    pour ce faire, utiliser le modèle de formulaire de rétractation, tel qu'annexé à l'article R. 121-1 du code de la
                    consommation et reproduit ci-dessous. Pour que le délai de rétractation soit respecté, il suffit que vous transmettiez
                    votre communication relative à l'exercice du droit de rétractation avant l'expiration du délai de rétractation. En cas
                    de rétractation de votre part, nous vous rembourserons tous les paiements reçus de Vous, sans retard excessif et, en
                    tout état de cause, au plus tard quatorze (14) jours à compter du jour où nous serons informés de votre décision de
                    rétractation. Nous procéderons au remboursement en utilisant le même moyen de paiement que celui que Vous aurez utilisé
                    pour la transaction initiale, sauf si vous convenez expressément d'un autre moyen. Ce remboursement n'occasionnera pas
                    de frais pour vous.
                </p>
                <p class="mb-5">
                    Conformément à l'article L. 121-21-5 du code de la consommation, si Vous avez expressément demandé, par email à
                    l'adresse suivante : passpermisfacilepermis@gmail.com ou par courrier à l'adresse suivante : EasyMonitor, 22 RUE CHARLES
                    LESCOT, 60700 PONT-SAINTE-MAXENCE, de commencer la(les) prestation(s) de services avant la fin du délai de rétractation,
                    et que Vous exercez votre droit de rétractation alors que les prestations ont commencé, Vous devrez payer à notre
                    Société un montant correspondant au service fourni jusqu'à la communication de Votre décision de rétractation, ce
                    montant étant proportionné au prix total de la(les) prestation(s) commandées.
                </p>
                <p class="mb-5">
                    Conformément à l'article L. 121-21-8 du code de la consommation, le droit de rétractation ne pourra être exercé lorsque
                    les services auront été pleinement exécutés avant la fin du délai de rétractation et dont l'exécution aura commencé
                    après votre accord préalable exprès et renoncement exprès à votre droit de rétractation.
                </p>
            </article>
        </section>
        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <article class="mb-5">
                <h2 class="bloc-title mb-5">Formulaire de rétractation</h2>
                <p class="mb-5">
                    À l'attention de : passpermisfacile , 22 rue charles lescot, mont sainte maxence, ou à l'adresse mail
                    passpermisfacilepermis@gmail.com
                </p>
                <p class="mb-5 flex items-baseline">Je soussigné(e) <span class="border-b border-gray-800 flex-1"></span></p>
                <p class="mb-5">vous notifie par la présente ma rétractation du contrat portant sur le contrat ci-dessous :</p>
                <p class="mb-10 flex items-baseline">
                    - Contrat de formation conclu le <span class="border-b border-gray-800 flex-1"></span>
                </p>
                <p class="mb-5">
                    Signature du consommateur [ou de son représentant (*)] (uniquement en cas de notification du présent formulaire sur
                    papier) :
                </p>
                <p class="mb-5">Date :</p>
                <p class="mb-5">(*) Rayez si mention inutile</p>
            </article>
        </section>
        <section class="page">
            <img src="/assets/logo.svg" alt="passpermisfacile" class="logo" />
            <article class="pb-5">
                <h2 class="bloc-title mb-5">Annexe 1 : Etapes de formation</h2>
                <p class="mb-5">Conformément au REMC, la formation s'articulera autour de ces quatre compétences</p>
                <ol class="md:pl-8 space-y-5 font-bold leading-tight">
                    <li>
                        <p class="block mb-5">1) Maîtrise du véhicule dans un trafic dense :</p>

                        <ul class="pl-5 list-dash">
                            <li>
                                Connaître les principaux organes et commandes du véhicule, effectuer des vérifications intérieures et
                                extérieures.
                            </li>
                            <li>Entrer, s'installer au poste de conduite et en sortir.</li>
                            <li>Tenir, tourner le volant et maintenir la trajectoire.</li>
                            <li>Démarrer et s'arrêter.</li>
                            <li>Doser l'accélaration et le freinage à diverses allures.</li>
                            <li>Utiliser la boîte de vitesses.</li>
                            <li>
                                Diriger la voiture en avant en ligne droite et en courbe en adoptant allure et trajectoire. Regarder autour
                                de soi et avertir.
                            </li>
                            <li>Effectuer une marche arrière et un demi-tour en sécurité.</li>
                        </ul>
                    </li>
                    <li>
                        <p class="block mb-5">2) Maîtrise du véhicule dans un trafic normal :</p>
                        <ul class="pl-5 list-dash">
                            <li>Rechercher la signalisation, les indices utiles et en tenir compte.</li>
                            <li>Positionner le véhicule sur la chaussée et choisir la voie de circulation.</li>
                            <li>Adapter l'allure aux situations.</li>
                            <li>Détecter, identifier et franchir les intersections suivant le régime de priorité.</li>
                            <li>Tourner à droite et à gauche en agglomération.</li>
                            <li>Franchir les ronds-points et les carrefours à sens giratoire.</li>
                            <li>S'arrêter et stationner en épi, en bataille et en créneau.</li>
                        </ul>
                    </li>
                    <li>
                        <p class="block mb-5">3) Partager la route avec les autres usagers :</p>
                        <ul class="pl-5 list-dash">
                            <li>Evaluer et maintenir les distances de sécurité.</li>
                            <li>Croiser, dépasser, être dépassé.</li>
                            <li>Passer des virages et conduire en déclivité.</li>
                            <li>
                                Connaître les caractéristiques des autres usagers et savoir se comporter à leur égard avec respect et
                                courtoisie.
                            </li>
                            <li>S'insérer, circuler et sortir d'une voie rapide.</li>
                            <li>Conduire dans une file de véhicules et dans une circulation dense.</li>
                            <li>Conduire quand l'adhérence et la visibilité sont réduites.</li>
                        </ul>
                    </li>
                    <li>
                        <p class="block mb-5">4) Pratiquer une conduite sûre, autonome et économe :</p>

                        <ul class="pl-5 list-dash">
                            <li>Suivre un itinéraire de manière autonome.</li>
                            <li>Préparer et effectuer un voyage longue distance en autonomie.</li>
                            <li>Connaître les principaux facteurs de risque au volant et les recommandations à appliquer.</li>
                            <li>Connaître les comportements à adapter en cas d'accident : protéger, alerter, secourir.</li>
                            <li>
                                Faire l'expérience des aides à la conduite du véhicule (régulateur, limiteur de vitesse, ABS, aides à la
                                navigation…).
                            </li>
                            <li>Avoir des notions sur l'entretien, le dépannage et les situations d'urgence.</li>
                            <li>Pratiquer l'éco-conduite.</li>
                        </ul>
                    </li>
                </ol>
            </article>
        </section>
    </div>
</template>
<style scoped lang="scss">
.page {
    @apply bg-white px-4 md:px-24 pt-24 aspect-[1/1.403] rounded-xl relative font-pdf max-md:text-xs;
    p {
        @apply tracking-normal;
    }
    .bloc-title {
        @apply md:text-3xl font-bold leading-normal;
    }
    .list-dash li {
        @apply relative pl-5 font-normal;
        &::before {
            content: '-';
            @apply absolute left-0 top-0;
        }
    }
    .logo {
        @apply absolute right-6 top-6 w-52 object-contain max-md:hidden;
    }
    .title-underline {
        @apply border-b-4 border-dark font-bold inline-block h-10;
    }
}
</style>
