<?php

namespace Database\Seeders;

use App\Models\Theory\TheoryAnswerOption;
use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryContentBank;
use App\Models\Theory\TheoryMediaAsset;
use App\Models\Theory\TheoryQuestion;
use App\Models\Theory\TheorySeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TheoryOriginalFrench2026PilotSeeder extends Seeder
{
    private const BANK = 'original-fr-2026';
    private const SOURCE = 'passpermisfacile-original-fr-2026-pilot';

    public function run(): void
    {
        $bank = TheoryContentBank::query()->where('code', self::BANK)->firstOrFail();
        $categories = TheoryCategory::query()->pluck('id', 'code');
        $questions = $this->questions();

        DB::transaction(function () use ($bank, $categories, $questions) {
            $questionIds = [];
            foreach ($questions as $index => $definition) {
                $number = $index + 1;
                $externalId = sprintf('PFF-ORIG-2026-%04d', $number);
                $webp = sprintf('theory/questions/original-fr-2026/pilot-%03d.webp', $number);
                $png = sprintf('theory/questions/original-fr-2026/pilot-%03d.png', $number);
                foreach ([$webp, $png] as $path) {
                    if (! is_file(storage_path('app/public/'.$path))) {
                        throw new \RuntimeException("Média pilote manquant : {$path}");
                    }
                }
                if (! isset($categories[$definition['category']])) {
                    throw new \RuntimeException("Catégorie introuvable : {$definition['category']}");
                }

                $question = TheoryQuestion::withTrashed()->firstOrNew([
                    'import_source' => self::SOURCE,
                    'external_id' => $externalId,
                ]);
                if ($question->exists && $question->content_bank_id !== $bank->id) {
                    throw new \RuntimeException("Identifiant pilote déjà associé à une autre banque : {$externalId}");
                }
                $question->fill([
                    'public_id' => $externalId,
                    'content_bank_id' => $bank->id,
                    'category_id' => $categories[$definition['category']],
                    'question_text' => $definition['question'],
                    'difficulty' => $definition['difficulty'],
                    'answer_type' => $definition['answer_type'] ?? 'single',
                    'media_type' => 'image',
                    'media_path' => $webp,
                    'image_path' => $webp,
                    'video_path' => null,
                    'audio_path' => null,
                    'explanation' => $definition['explanation'],
                    'official_reference' => 'Contenu original PassPermisFacile ; validation réglementaire et éditoriale requise avant publication.',
                    'content_origin' => 'original-editorial-draft',
                    'license_reference' => 'Création originale PassPermisFacile ; média généré pour revue éditoriale.',
                    'review_status' => 'pending_editorial_review',
                    'status' => 'draft',
                    'is_active' => false,
                    'requires_review' => true,
                    'review_notes' => 'Pilote original 2026 : vérification Code de la route, signalisation et cohérence média requise.',
                    'content_hash' => hash('sha256', $definition['question']),
                    'deleted_at' => null,
                ]);
                $question->save();
                $questionIds[] = $question->id;

                $question->options()->delete();
                $question->parts()->delete();
                $question->options()->createMany(collect($definition['options'])->values()->map(fn (array $option, int $optionIndex) => [
                    'label' => $option[0],
                    'is_correct' => ! isset($definition['parts']) && (bool) $option[1],
                    'sort_order' => $optionIndex + 1,
                ])->all());
                foreach ($definition['parts'] ?? [] as $partIndex => $part) {
                    $question->parts()->create([
                        'question_text' => $part['question'],
                        'answer_type' => $part['answer_type'] ?? 'single',
                        'explanation' => $part['explanation'],
                        'correct_option_positions' => $part['correct'],
                        'sort_order' => $partIndex + 1,
                    ]);
                }

                foreach ([$webp, $png] as $assetPath) {
                    TheoryMediaAsset::updateOrCreate(['path' => $assetPath], [
                        'content_bank_id' => $bank->id,
                        'question_id' => $question->id,
                        'kind' => 'image',
                        'original_path' => $assetPath === $webp ? $png : null,
                        'creative_brief' => $definition['brief'],
                        'creation_method' => 'original AI-assisted visual draft',
                        'provenance' => 'PassPermisFacile original pilot media; no ENPC or Prépacode media used.',
                        'file_hash' => hash_file('sha256', storage_path('app/public/'.$assetPath)),
                        'editorial_review_status' => 'pending',
                        'review_notes' => 'Vérifier la fidélité de la signalisation et des règles avant publication.',
                    ]);
                }
            }

            $series = TheorySeries::withTrashed()->firstOrNew([
                'content_bank_id' => $bank->id,
                'title' => 'Série Pilote — Code de la Route Original 2026',
            ]);
            $series->fill([
                'type' => 'training',
                'status' => 'draft',
                'duration_minutes' => null,
                'description' => 'Pilote de 40 écrans originaux, réservé à la revue éditoriale et à l’aperçu administrateur.',
                'sort_order' => 1000,
                'deleted_at' => null,
            ]);
            $series->save();
            $series->seriesQuestions()->delete();
            foreach ($questionIds as $sortOrder => $questionId) {
                $series->seriesQuestions()->create(['question_id' => $questionId, 'sort_order' => $sortOrder + 1]);
            }
        });
    }

    private function questions(): array
    {
        return [
            ['category'=>'circulation-routiere','difficulty'=>'easy','question'=>'À l’approche d’un passage piéton, une personne attend au bord de la chaussée et regarde vers la circulation. Quelle attitude adoptez-vous ?','options'=>[['Je ralentis et je me tiens prêt à céder le passage.',true],['Je conserve ma vitesse car elle n’est pas encore engagée.',false],['Je klaxonne pour lui signaler mon arrivée.',false],['Je me décale vers la gauche sans vérifier.',false]],'explanation'=>'Un conducteur doit anticiper l’intention manifeste de traverser et permettre au piéton de franchir la chaussée en sécurité.','brief'=>'Vue conducteur sur un passage piéton français, piéton clairement prêt à traverser, véhicule ralentissant.'],
            ['category'=>'circulation-routiere','difficulty'=>'easy','question'=>'Le feu passe au rouge alors que vous approchez de la ligne d’arrêt. Que faites-vous ?','options'=>[['Je m’arrête avant la ligne d’arrêt.',true],['Je poursuis si aucun véhicule ne vient.',false],['Je m’arrête après le passage piéton.',false],['Je recule après avoir dépassé la ligne.',false]],'explanation'=>'Le feu rouge impose l’arrêt avant la ligne d’effet du feu.','brief'=>'Carrefour français avec feu rouge net, ligne d’arrêt et passage piéton visibles depuis le conducteur.'],
            ['category'=>'circulation-routiere','difficulty'=>'easy','question'=>'Vous arrivez à un STOP avec une bonne visibilité. Quelle action est obligatoire ?','options'=>[['Marquer un arrêt complet puis céder le passage si nécessaire.',true],['Ralentir fortement sans immobiliser le véhicule.',false],['Passer si personne n’est visible.',false],['Klaxonner avant de franchir l’intersection.',false]],'explanation'=>'Le panneau STOP impose une immobilisation complète, même si l’intersection semble libre.','brief'=>'Intersection de village française avec panneau STOP, ligne STOP et visibilité latérale.'],
            ['category'=>'autres-usagers','difficulty'=>'medium','question'=>'Vous dépassez un cycliste hors agglomération sur une route à deux sens. Que vérifiez-vous en priorité ?','options'=>[['Que je peux laisser au moins 1,5 mètre latéral et revenir sans danger.',true],['Que le cycliste roule près du bord, ce qui suffit.',false],['Que ma voiture est plus rapide.',false],['Que je peux franchir une ligne continue.',false]],'explanation'=>'Hors agglomération, le dépassement d’un cycliste exige notamment un écart latéral minimal de 1,5 mètre et des conditions sûres.','brief'=>'Route française rurale, cycliste, marquage discontinu et espace latéral de dépassement visible.'],
            ['category'=>'autres-usagers','difficulty'=>'medium','question'=>'Vous tournez à droite et une piste cyclable traverse votre trajectoire. Que faites-vous ?','options'=>[['Je contrôle les cyclistes et je leur cède le passage lorsqu’ils sont prioritaires.',true],['Je tourne vite pour passer avant eux.',false],['Je m’arrête sur la piste cyclable.',false],['Je regarde seulement le rétroviseur intérieur.',false]],'explanation'=>'Avant de tourner, il faut contrôler les usagers vulnérables, notamment sur une piste cyclable qui croise la trajectoire.','brief'=>'Vue conducteur avant un virage à droite, piste cyclable traversante et cycliste prioritaire.'],
            ['category'=>'autres-usagers','difficulty'=>'medium','question'=>'Un bus signale son départ d’un arrêt en agglomération. Quelle attitude est la plus sûre ?','options'=>[['Je ralentis et je facilite son insertion si les conditions le permettent.',true],['Je l’accélère pour le dépasser immédiatement.',false],['Je me colle derrière lui pour l’empêcher de sortir.',false],['Je me déporte sans contrôler ma voie.',false]],'explanation'=>'En agglomération, il faut anticiper le départ d’un bus et faciliter son insertion sans créer de danger.','brief'=>'Rue française en agglomération, autobus quittant un arrêt avec clignotant, conducteur ralentissant.'],
            ['category'=>'autres-usagers','difficulty'=>'hard','question'=>'Vous approchez d’un tramway à un carrefour. Quel principe devez-vous garder à l’esprit ?','options'=>[['Je respecte la signalisation et je ne gêne jamais sa trajectoire.',true],['Je passe devant lui car il est sur rails.',false],['Je m’arrête sur ses rails pour attendre.',false],['Je le dépasse par la droite sans vérifier.',false]],'explanation'=>'Le tramway suit une trajectoire contrainte ; la signalisation et le dégagement de ses voies doivent être strictement respectés.','brief'=>'Carrefour français avec rails de tramway, feu dédié et véhicule attendant hors des rails.'],
            ['category'=>'route','difficulty'=>'medium','question'=>'Sur une chaussée mouillée, comment adaptez-vous votre distance de sécurité ?','options'=>[['Je l’augmente nettement.',true],['Je la conserve car mes pneus sont récents.',false],['Je la réduis pour empêcher une insertion.',false],['Je roule sur la ligne médiane.',false]],'explanation'=>'La pluie augmente les distances de freinage et réduit l’adhérence : la distance de sécurité doit être augmentée.','brief'=>'Vue conducteur sous pluie légère, véhicule devant, chaussée mouillée et distance importante.'],
            ['category'=>'route','difficulty'=>'hard','question'=>'Sur une route à double sens, la visibilité est limitée par le sommet d’une côte. Que faites-vous ?','options'=>[['Je renonce à dépasser.',true],['Je dépasse rapidement si le véhicule devant est lent.',false],['Je franchis la ligne continue si personne n’est visible.',false],['Je suis le véhicule devant de très près.',false]],'explanation'=>'Un dépassement exige une visibilité suffisante sur la voie opposée et la possibilité de se rabattre sans danger.','brief'=>'Route française à double sens au sommet d’une côte, visibilité insuffisante, voiture restant derrière un véhicule lent.'],
            ['category'=>'route','difficulty'=>'medium','question'=>'En entrant sur l’autoroute par une voie d’insertion, que devez-vous faire ?','options'=>[['Adapter ma vitesse et céder le passage aux véhicules déjà sur l’autoroute.',true],['M’arrêter à la fin de la voie dans tous les cas.',false],['Forcer le passage avec le clignotant.',false],['Rouler très lentement sur toute la voie.',false]],'explanation'=>'Les véhicules déjà engagés sur l’autoroute sont prioritaires ; la voie d’insertion sert à adapter sa vitesse et trouver un créneau sûr.','brief'=>'Vue conducteur sur voie d’insertion d’autoroute française, trafic sur voie de droite et créneau d’insertion.'],
            ['category'=>'conducteur','difficulty'=>'medium','question'=>'Après deux heures de conduite, vos paupières sont lourdes et votre attention baisse. Quelle réponse est adaptée ?','options'=>[['Je fais une pause dans un lieu sûr.',true],['J’ouvre la fenêtre et je continue.',false],['Je mets la musique plus fort.',false],['Je roule plus vite pour arriver plus tôt.',false]],'explanation'=>'Les signes de fatigue imposent une pause : les astuces comme ouvrir la fenêtre ne remplacent pas le repos.','brief'=>'Aire de repos française, conducteur stationné en sécurité après un long trajet.'],
            ['category'=>'conducteur','difficulty'=>'easy','question'=>'Avant une soirée où vous pourriez boire de l’alcool, comment préparez-vous votre retour ?','options'=>[['Je prévois un conducteur sobre ou une autre solution de retour.',true],['Je décide après avoir bu si je me sens capable.',false],['Je bois du café avant de conduire.',false],['Je roule plus lentement pour compenser.',false]],'explanation'=>'Seul un retour sans conduite sous l’influence protège réellement les usagers ; le café ou le ressenti ne réduisent pas l’alcoolémie.','brief'=>'Scène sobre de préparation de trajet : conducteur désigné remettant les clés, voiture stationnée.'],
            ['category'=>'conducteur','difficulty'=>'easy','question'=>'Votre téléphone sonne pendant que vous conduisez. Que faites-vous ?','options'=>[['Je ne le manipule pas et j’attends d’être arrêté dans un lieu autorisé.',true],['Je réponds brièvement à la main.',false],['Je lis la notification au feu rouge.',false],['Je le pose sur mes genoux.',false]],'explanation'=>'Tenir ou manipuler un téléphone en conduisant détourne l’attention et est interdit.','brief'=>'Vue conducteur, téléphone rangé hors main, route urbaine dégagée devant.'],
            ['category'=>'conducteur','difficulty'=>'medium','question'=>'Avant de changer de voie, quelle séquence est la plus sûre ?','options'=>[['Rétroviseurs, angle mort, clignotant, puis manœuvre si la voie est libre.',true],['Clignotant puis déplacement immédiat.',false],['Accélération puis contrôle après la manœuvre.',false],['Regard uniquement dans le rétroviseur intérieur.',false]],'explanation'=>'Le changement de voie demande un contrôle complet, le signalement et une manœuvre seulement si l’espace est libre.','brief'=>'Voie rapide française, rétroviseur latéral montrant angle mort dégagé avant changement de voie.'],
            ['category'=>'prendre-quitter-vehicule','difficulty'=>'easy','question'=>'Avant d’ouvrir votre portière côté chaussée après un stationnement, que vérifiez-vous ?','options'=>[['L’arrivée éventuelle d’un cycliste ou d’un autre usager.',true],['Seulement si la voiture derrière est loin.',false],['Rien, car je suis à l’arrêt.',false],['Que mes feux sont allumés.',false]],'explanation'=>'L’ouverture d’une portière peut mettre en danger les cyclistes et deux-roues ; un contrôle est indispensable.','brief'=>'Voiture stationnée en ville française, conducteur vérifiant un cycliste avant ouverture de portière.'],
            ['category'=>'prendre-quitter-vehicule','difficulty'=>'medium','question'=>'Vous transportez un objet lourd dans le coffre. Quelle précaution prenez-vous ?','options'=>[['Je le cale ou l’attache pour éviter son déplacement.',true],['Je le pose librement contre la banquette.',false],['Je le mets sur la plage arrière.',false],['Je laisse le coffre entrouvert.',false]],'explanation'=>'Un chargement doit être stable afin de ne pas gêner la conduite ni devenir un projectile lors d’un freinage.','brief'=>'Coffre de voiture avec charge lourde correctement sanglée, sans marque visible.'],
            ['category'=>'prendre-quitter-vehicule','difficulty'=>'easy','question'=>'Avant de démarrer, comment réglez-vous votre siège ?','options'=>[['Pour atteindre les pédales et le volant sans tendre les jambes ni les bras.',true],['Le plus loin possible pour avoir de la place.',false],['Très près du volant pour mieux voir.',false],['Après avoir commencé à rouler.',false]],'explanation'=>'Une position adaptée permet de garder la maîtrise des commandes et une posture efficace au freinage.','brief'=>'Habitacle voiture, conducteur réglant correctement siège et volant avant départ.'],
            ['category'=>'prendre-quitter-vehicule','difficulty'=>'medium','question'=>'Un enfant descend du véhicule côté trottoir. Quelle précaution est la plus adaptée ?','options'=>[['Je vérifie qu’il sort côté trottoir et j’encadre sa descente.',true],['Je le laisse ouvrir n’importe quelle portière.',false],['Je m’arrête en double file sans signaler.',false],['Je le fais descendre côté chaussée pour gagner du temps.',false]],'explanation'=>'La descente côté trottoir limite l’exposition à la circulation ; l’adulte doit sécuriser l’opération.','brief'=>'Voiture arrêtée légalement au bord d’un trottoir, adulte accompagnant enfant côté trottoir.'],
            ['category'=>'mecanique-equipements','difficulty'=>'medium','question'=>'À quel moment contrôlez-vous la pression des pneus ?','options'=>[['À froid, selon les préconisations du véhicule.',true],['Après un long trajet rapide.',false],['Uniquement lorsque le témoin s’allume.',false],['Jamais si les pneus semblent gonflés.',false]],'explanation'=>'La pression se contrôle à froid, car la température après roulage fausse la mesure.','brief'=>'Station de gonflage française, conducteur contrôlant un pneu froid avec manomètre.'],
            ['category'=>'mecanique-equipements','difficulty'=>'easy','question'=>'Le témoin rouge de pression d’huile s’allume en roulant. Que faites-vous ?','options'=>[['Je m’arrête dès que possible en sécurité et je coupe le moteur.',true],['Je continue jusqu’au prochain entretien.',false],['J’accélère pour faire disparaître le témoin.',false],['Je redémarre immédiatement sans vérification.',false]],'explanation'=>'Un témoin rouge d’huile peut signaler un risque grave pour le moteur ; il faut s’arrêter en sécurité.','brief'=>'Tableau de bord générique avec voyant d’huile rouge, voiture s’arrêtant sur zone sûre.'],
            ['category'=>'mecanique-equipements','difficulty'=>'medium','question'=>'La nuit, vous croisez un autre véhicule. Quel éclairage utilisez-vous ?','options'=>[['Les feux de croisement.',true],['Les feux de route en permanence.',false],['Uniquement les feux de position.',false],['Les feux de détresse.',false]],'explanation'=>'Les feux de croisement évitent d’éblouir l’usager venant en face tout en éclairant la route.','brief'=>'Route française de nuit, deux voitures se croisant avec feux de croisement non éblouissants.'],
            ['category'=>'mecanique-equipements','difficulty'=>'medium','question'=>'Avant un trajet hivernal en montagne, quelle préparation est pertinente ?','options'=>[['Je vérifie les équipements hivernaux adaptés à l’itinéraire et aux conditions.',true],['Je pars avec des pneus usés pour mieux accrocher.',false],['Je dégonfle fortement les pneus sans conseil.',false],['Je compte uniquement sur les aides électroniques.',false]],'explanation'=>'L’équipement doit être adapté aux conditions et aux obligations locales : pneus adaptés ou chaînes selon la situation.','brief'=>'Voiture française en préparation hivernale, chaînes rangées et route de montagne enneigée en arrière-plan.'],
            ['category'=>'securite-passager-vehicule','difficulty'=>'easy','question'=>'Un passager arrière adulte refuse de boucler sa ceinture. Que faites-vous ?','options'=>[['Je ne démarre pas tant que chaque occupant n’est pas attaché.',true],['Je démarre car seules les places avant comptent.',false],['Je le laisse choisir s’il est majeur.',false],['Je roule lentement sans ceinture.',false]],'explanation'=>'La ceinture est obligatoire pour chaque occupant lorsqu’elle est disponible ; elle protège aussi les autres passagers.','brief'=>'Habitacle arrière, adulte bouclant correctement sa ceinture avant départ.'],
            ['category'=>'securite-passager-vehicule','difficulty'=>'medium','question'=>'Pour un jeune enfant, quel principe est correct ?','options'=>[['Utiliser un dispositif de retenue adapté à sa taille et à son poids.',true],['Le placer directement avec une ceinture adulte dans tous les cas.',false],['Le laisser debout à l’arrière.',false],['Le tenir dans les bras à l’avant.',false]],'explanation'=>'Les enfants doivent être retenus par un dispositif homologué et adapté à leur morphologie.','brief'=>'Siège enfant correctement installé à l’arrière d’une voiture, ceinture visible.'],
            ['category'=>'securite-passager-vehicule','difficulty'=>'medium','question'=>'La visibilité arrière est réduite par des objets sur la plage arrière. Que faites-vous ?','options'=>[['Je les range avant de partir.',true],['Je les laisse s’ils sont légers.',false],['Je compense en regardant seulement devant.',false],['Je les bloque avec un manteau.',false]],'explanation'=>'Les objets peuvent masquer la visibilité et devenir dangereux lors d’un freinage ; ils doivent être rangés.','brief'=>'Plage arrière de voiture dégagée, objets rangés dans coffre, bonne visibilité dans rétroviseur.'],
            ['category'=>'securite-passager-vehicule','difficulty'=>'easy','question'=>'Avant de partir, un passager demande à enlever l’appuie-tête pour être plus confortable. Quelle réponse donnez-vous ?','options'=>[['Je le laisse réglé à une hauteur protectrice.',true],['Je le retire complètement.',false],['Je le baisse au niveau des épaules.',false],['Je le tourne vers l’arrière.',false]],'explanation'=>'L’appuie-tête correctement réglé limite le risque de traumatisme cervical en cas de choc arrière.','brief'=>'Appuie-tête automobile correctement réglé derrière la tête d’un passager.'],
            ['category'=>'premiers-secours','difficulty'=>'medium','question'=>'Vous arrivez le premier sur un accident récent. Quelle est la première priorité ?','options'=>[['Protéger la zone sans me mettre en danger.',true],['Déplacer immédiatement les blessés.',false],['Filmer la scène pour prévenir les autres.',false],['Partir chercher un objet personnel.',false]],'explanation'=>'La protection évite un suraccident : se mettre en sécurité, signaler et évaluer avant toute autre action.','brief'=>'Accident léger fictif, véhicule arrêté en sécurité, triangle et gilet haute visibilité sans blessé identifiable.'],
            ['category'=>'premiers-secours','difficulty'=>'medium','question'=>'Après avoir sécurisé une zone d’accident, que communiquez-vous aux secours ?','options'=>[['Le lieu précis, la nature de l’accident et le nombre de personnes concernées.',true],['Uniquement mon nom.',false],['Des suppositions sur la responsabilité.',false],['La marque des véhicules seulement.',false]],'explanation'=>'Les secours ont besoin d’informations factuelles : localisation, risques, nombre et état apparent des victimes.','brief'=>'Conducteur sécurisé appelant les secours au bord d’une route, repère kilométrique visible sans données personnelles.'],
            ['category'=>'premiers-secours','difficulty'=>'hard','question'=>'Une personne est inconsciente mais respire normalement. Quelle conduite générale est adaptée en attendant les secours ?','options'=>[['La surveiller, alerter et appliquer les gestes de secours adaptés sans la déplacer inutilement.',true],['La faire marcher pour la réveiller.',false],['Lui donner à boire.',false],['La laisser seule pour chercher de l’aide.',false]],'explanation'=>'Une personne inconsciente qui respire nécessite l’alerte, la surveillance et les gestes de secours appropriés, sans gestes dangereux.','brief'=>'Scène de premiers secours non graphique : témoin surveillant une personne allongée, téléphone d’alerte, zone sécurisée.'],
            ['category'=>'premiers-secours','difficulty'=>'easy','question'=>'Où devez-vous ranger le gilet rétro-réfléchissant pour pouvoir le mettre avant de sortir en urgence ?','options'=>[['À portée de main dans l’habitacle.',true],['Au fond du coffre sous les bagages.',false],['Dans la boîte à gants verrouillée avec la clé.',false],['À l’extérieur du véhicule.',false]],'explanation'=>'Le gilet doit être accessible depuis l’habitacle afin de pouvoir le porter avant d’exposer son corps à la circulation.','brief'=>'Habitacle véhicule, gilet haute visibilité rangé dans une poche de porte accessible.'],
            ['category'=>'environnement','difficulty'=>'easy','question'=>'À l’approche d’un feu qui vient de passer au rouge, quelle conduite limite aussi la consommation ?','options'=>[['Relâcher l’accélérateur tôt et anticiper l’arrêt.',true],['Accélérer jusqu’au dernier moment.',false],['Freiner brutalement au feu.',false],['Laisser tourner le moteur très haut dans les tours.',false]],'explanation'=>'L’anticipation réduit les freinages inutiles, améliore la sécurité et limite la consommation.','brief'=>'Vue conducteur anticipant un feu rouge à distance, circulation fluide et décélération douce.'],
            ['category'=>'environnement','difficulty'=>'medium','question'=>'Votre véhicule est chargé inutilement d’objets lourds pour un trajet quotidien. Quel effet est le plus probable ?','options'=>[['Une consommation plus élevée.',true],['Aucun effet sur le véhicule.',false],['Une meilleure distance de freinage.',false],['Une usure nulle des pneus.',false]],'explanation'=>'Une masse transportée inutilement augmente l’énergie nécessaire au déplacement et peut accroître la consommation.','brief'=>'Coffre avec objets lourds inutiles retirés avant trajet, véhicule prêt à rouler.'],
            ['category'=>'environnement','difficulty'=>'medium','question'=>'Pour réduire le bruit et la consommation en ville, quelle conduite privilégiez-vous ?','options'=>[['Une accélération progressive et le bon rapport de vitesse.',true],['Des accélérations très fortes entre deux feux.',false],['Le régime moteur élevé en permanence.',false],['Le klaxon pour fluidifier le trafic.',false]],'explanation'=>'Une conduite souple et anticipée réduit bruit, consommation et émissions tout en améliorant le confort.','brief'=>'Rue urbaine française, conduite fluide à vitesse modérée, piétons et vélos présents.'],
            ['category'=>'environnement','difficulty'=>'easy','question'=>'À l’arrêt prolongé, moteur chaud et en sécurité, quelle habitude est préférable ?','options'=>[['Éviter de laisser le moteur tourner inutilement.',true],['Maintenir le moteur accéléré.',false],['Faire des appels de phares répétés.',false],['Ouvrir le capot sur la chaussée.',false]],'explanation'=>'Faire tourner inutilement le moteur augmente consommation, bruit et émissions sans avantage de sécurité.','brief'=>'Voiture garée légalement, moteur coupé, conducteur attendant hors circulation.'],
            ['category'=>'notions-diverses','difficulty'=>'easy','question'=>'Quels documents ou informations doivent pouvoir être présentés lors d’un contrôle routier selon votre situation ?','options'=>[['Les documents requis pour le conducteur et le véhicule, valides et accessibles.',true],['Seulement une photo de la voiture.',false],['Uniquement un justificatif de domicile.',false],['Aucun document si je connais mon numéro de permis.',false]],'explanation'=>'Le conducteur doit pouvoir présenter les documents exigibles et maintenir les informations du véhicule à jour.','brief'=>'Conducteur préparant documents véhicule et permis sans montrer de données lisibles.'],
            ['category'=>'notions-diverses','difficulty'=>'medium','question'=>'Un panneau indique une zone à faibles émissions avec conditions d’accès. Que faites-vous ?','options'=>[['Je vérifie que mon véhicule est autorisé avant d’entrer.',true],['Je l’ignore si la circulation est fluide.',false],['Je retire ma plaque pour éviter le contrôle.',false],['Je suis le véhicule devant sans lire le panneau.',false]],'explanation'=>'Les restrictions de circulation doivent être respectées ; il faut vérifier l’autorisation applicable au véhicule et à la zone.','brief'=>'Entrée de zone urbaine française avec panneau de restriction environnementale stylisé, véhicule lisant la signalisation.'],
            ['category'=>'notions-diverses','difficulty'=>'medium','question'=>'Vous êtes témoin d’un comportement agressif d’un autre conducteur. Quelle réaction réduit le risque ?','options'=>[['Je garde mes distances et je n’entre pas dans l’escalade.',true],['Je réponds par des gestes agressifs.',false],['Je le suis jusqu’à son arrêt.',false],['Je bloque volontairement sa voie.',false]],'explanation'=>'La désescalade, la distance et le respect des règles limitent le risque d’accident ou de conflit.','brief'=>'Trafic urbain, conducteur gardant une distance calme derrière véhicule agité, aucune confrontation.'],
            ['category'=>'notions-diverses','difficulty'=>'easy','question'=>'Avant de prêter votre véhicule, quel contrôle est pertinent ?','options'=>[['Vérifier que la personne est autorisée et apte à conduire.',true],['Lui donner les clés sans poser de question.',false],['Lui demander seulement de faire le plein.',false],['Retirer les rétroviseurs.',false]],'explanation'=>'Le propriétaire doit s’assurer que le conducteur est habilité et en état de conduire en sécurité.','brief'=>'Remise de clés entre adultes responsables près d’une voiture stationnée, sans texte ni marque.'],
            ['category'=>'circulation-routiere','difficulty'=>'medium','question'=>'Situation à deux questions : observez ce carrefour avec passage piéton et feu rouge.','answer_type'=>'single','options'=>[['Oui',false],['Non',false],['Oui',false],['Non',false]],'parts'=>[
                ['question'=>'Question 1 : Puis-je franchir le passage piéton lorsque le feu est rouge ?', 'correct'=>[2], 'explanation'=>'Non. Le feu rouge impose l’arrêt avant la ligne, même si le passage piéton semble libre.'],
                ['question'=>'Question 2 : Dois-je rester attentif aux piétons avant de redémarrer au vert ?', 'correct'=>[3], 'explanation'=>'Oui. Le redémarrage exige de vérifier que la traversée est dégagée et sûre.'],
            ],'explanation'=>'Deux règles complémentaires : arrêt au rouge et vigilance envers les piétons au redémarrage.','brief'=>'Carrefour français réaliste, feu rouge, passage piéton et piéton visible en attente ; scène destinée à deux questions partagées.'],
            ['category'=>'route','difficulty'=>'hard','question'=>'Situation à deux questions : vous approchez d’un rond-point sous la pluie, avec des véhicules déjà engagés.','answer_type'=>'single','options'=>[['Oui',false],['Non',false],['Oui',false],['Non',false]],'parts'=>[
                ['question'=>'Question 1 : Dois-je céder le passage aux véhicules qui circulent déjà sur l’anneau ?', 'correct'=>[1], 'explanation'=>'Oui. Le cédez-le-passage à l’entrée impose de laisser passer les véhicules déjà engagés.'],
                ['question'=>'Question 2 : Dois-je augmenter ma prudence et ma distance sur chaussée mouillée ?', 'correct'=>[3], 'explanation'=>'Oui. La pluie réduit l’adhérence et demande une conduite plus progressive.'],
            ],'explanation'=>'Le rond-point impose le respect du cédez-le-passage ; la chaussée humide renforce l’exigence de prudence.','brief'=>'Vue conducteur sous pluie légère à l’entrée d’un rond-point français, cédez-le-passage visible et voitures circulant déjà.'],
        ];
    }
}
