<?php

namespace App\Document;

use App\Entity\Dokument;
use App\Entity\User;

abstract class AbstractDocument
{
    protected string $type;
    protected string $title;
    protected string $category;
    protected string $description;
    protected array $requiredFields = [];
    protected array $signers = [];

    abstract public function getType(): string;
    abstract public function getTitle(): string;
    abstract public function getCategory(): string;
    abstract public function getDescription(): string;
    
    public function generateContent(array $data): string
    {
        return '';
    }

    abstract public function getTemplateName(): string;
    abstract public function getRequiredFields(): array;
    abstract public function getSignersConfig(): array;

    public function validateData(array $data): array
    {
        $errors = [];
        
        foreach ($this->getRequiredFields() as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = sprintf('Pole "%s" jest wymagane', $field);
            }
        }
        
        return $errors;
    }

    public function prepareTemplateData(Dokument $dokument, array $data): array
    {
        $templateData = [];

        $templateData['numer_dokumentu'] = $dokument->getNumerDokumentu();
        $templateData['data'] = date('d.m.Y');

        try {
            $templateData['data_wejscia'] = $dokument->getDataWejsciaWZycie()->format('d.m.Y');
        } catch (\Error $e) {
            $templateData['data_wejscia'] = date('d.m.Y');
        }
        if ($dokument->getZebranieOddzialu()) {
            $zebranieOddzialu = $dokument->getZebranieOddzialu();

            if ($zebranieOddzialu->getOddzial()) {
                $templateData['oddzial'] = $zebranieOddzialu->getOddzial()->getNazwa();
            }

            if ($zebranieOddzialu->getDataRozpoczecia()) {
                $templateData['data_zebrania'] = $zebranieOddzialu->getDataRozpoczecia()->format('d.m.Y');
            }

            if ($zebranieOddzialu->getOddzial() && $zebranieOddzialu->getOddzial()->getOkreg()) {
                $templateData['okreg'] = $zebranieOddzialu->getOddzial()->getOkreg()->getNazwa();
            }
        }
        if ($dokument->getZebranieOkregu()) {
            $zebranieOkregu = $dokument->getZebranieOkregu();

            if ($zebranieOkregu->getOkreg()) {
                $templateData['okreg'] = $zebranieOkregu->getOkreg()->getNazwa();
            }

            if ($zebranieOkregu->getDataUtworzenia()) {
                $templateData['data_zebrania'] = $zebranieOkregu->getDataUtworzenia()->format('d.m.Y');
            }
        }

        if (!isset($templateData['okreg']) && $dokument->getOkreg()) {
            $templateData['okreg'] = $dokument->getOkreg()->getNazwa();
        }

        if ($dokument->getKandydat()) {
            $this->addUserData($templateData, $dokument->getKandydat(), '');
        } elseif ($dokument->getCzlonek()) {
            $this->addUserData($templateData, $dokument->getCzlonek(), '');
        } else {
            $templateData['imie_nazwisko'] = 'BRAK DANYCH';
            $templateData['user_id'] = 'N/A';
            $templateData['numer_w_partii'] = 'N/A';

            if (isset($data['kandydat']) && $data['kandydat'] instanceof User) {
                $this->addUserData($templateData, $data['kandydat'], 'kandydat');
            } elseif (isset($data['czlonek']) && $data['czlonek'] instanceof User) {
                $this->addUserData($templateData, $data['czlonek'], 'czlonek');
            }
        }

        if (isset($data['powolywan_czlonek']) && $data['powolywan_czlonek'] instanceof User) {
            $this->addUserData($templateData, $data['powolywan_czlonek'], 'powolywan');
        }

        if (isset($data['odwolywan_czlonek']) && $data['odwolywan_czlonek'] instanceof User) {
            $this->addUserData($templateData, $data['odwolywan_czlonek'], 'odwolywan');
        }

        if (isset($data['protokolant']) && $data['protokolant'] instanceof User) {
            $this->addUserData($templateData, $data['protokolant'], 'protokolant');
        }

        if (isset($data['prowadzacy']) && $data['prowadzacy'] instanceof User) {
            $this->addUserData($templateData, $data['prowadzacy'], 'prowadzacy');
        }

        if ($dokument->getZebranieOddzialu()) {
            if ($dokument->getZebranieOddzialu()->getProtokolant()) {
                $protokolant = $dokument->getZebranieOddzialu()->getProtokolant();
                $templateData['protokolant'] = $protokolant->getFullName();
            }
            if ($dokument->getZebranieOddzialu()->getProwadzacy()) {
                $prowadzacy = $dokument->getZebranieOddzialu()->getProwadzacy();
                $templateData['prowadzacy'] = $prowadzacy->getFullName();
            }
        }

        if ($dokument->getZebranieOkregu()) {
            if ($dokument->getZebranieOkregu()->getProtokolant()) {
                $protokolant = $dokument->getZebranieOkregu()->getProtokolant();
                $templateData['protokolant'] = $protokolant->getFullName();
                $templateData['sekretarz_zgromadzenia'] = $protokolant->getFullName();
                $templateData['sekretarz_walnego'] = $protokolant->getFullName();
            }
            if ($dokument->getZebranieOkregu()->getProwadzacy()) {
                $prowadzacy = $dokument->getZebranieOkregu()->getProwadzacy();
                $templateData['prowadzacy'] = $prowadzacy->getFullName();
                $templateData['przewodniczacy_zgromadzenia'] = $prowadzacy->getFullName();
                $templateData['prowadzacy_walnego'] = $prowadzacy->getFullName();
                $templateData['przewodniczacy_walnego'] = $prowadzacy->getFullName();
            }
            if ($dokument->getZebranieOkregu()->getObserwator()) {
                $templateData['obserwator_walnego'] = $dokument->getZebranieOkregu()->getObserwator()->getFullName();
            }
        }

        $signers = [];
        foreach ($dokument->getPodpisy() as $podpis) {
            $user = $podpis->getPodpisujacy();
            if ($user) {
                $signers[] = $user;
            }
        }

        if (count($signers) >= 1) {
            $this->addSignerDataWithoutFallback($templateData, $signers[0]);
        }
        if (count($signers) >= 2) {
            $templateData['czlonek_zarzadu'] = $signers[1]->getFullName();
        }

        if ($dokument->getPodpisy()->isEmpty() && $dokument->getTworca()) {
            $this->addSignerData($templateData, $dokument->getTworca());
        }

        $this->addSignaturesToTemplate($templateData, $dokument);

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) && empty($value)) {
                    continue;
                }

                if (is_array($value) && isset($value['date'])) {
                    try {
                        $date = new \DateTime($value['date']);
                        $templateData[$key] = $date->format('d.m.Y');
                    } catch (\Exception $e) {
                        $templateData[$key] = '';
                    }
                    continue;
                }

                if ($value instanceof \App\Entity\Oddzial) {
                    $templateData[$key] = $value->getNazwa();
                    continue;
                }

                $templateData[$key] = $value;
            }
        }

        $requiredVars = [
            'imie_nazwisko' => 'BRAK DANYCH',
            'user_id' => 'N/A',
            'numer_w_partii' => 'N/A',
            'funkcja' => 'Członek',
            'okreg' => 'N/A',
            'oddzial' => 'N/A',
        ];

        foreach ($requiredVars as $var => $defaultValue) {
            if (!isset($templateData[$var])) {
                $templateData[$var] = $defaultValue;
            }
        }

        return $templateData;
    }

    protected function addUserData(array &$templateData, User $user, string $prefix = ''): void
    {
        $templateData['imie_nazwisko'] = $user->getFullName();
        $templateData['user_id'] = $user->getId();
        $templateData['numer_w_partii'] = $user->getId();

        $templateData['email'] = $user->getEmail() ?: '';
        $templateData['telefon'] = $user->getTelefon() ?: '';
        $templateData['adres'] = $user->getAdresZamieszkania() ?: '';

        if ($user->getDataUrodzenia()) {
            $today = new \DateTime();
            $birthDate = $user->getDataUrodzenia();
            $age = $today->diff($birthDate)->y;
            $templateData['wiek'] = $age;
        } else {
            $templateData['wiek'] = '';
        }

        $templateData['pelnione_funkcje'] = $this->formatUserRoles($user);
        
        if ($prefix) {
            $templateData[$prefix . '_imie_nazwisko'] = $user->getFullName();
            $templateData[$prefix . '_user_id'] = $user->getId();
            $templateData[$prefix . '_numer_w_partii'] = $user->getId();
            $templateData[$prefix . '_email'] = $user->getEmail() ?: '';
            $templateData[$prefix . '_telefon'] = $user->getTelefon() ?: '';
            $templateData[$prefix . '_adres'] = $user->getAdresZamieszkania() ?: '';
            
            if ($user->getDataUrodzenia()) {
                $today = new \DateTime();
                $birthDate = $user->getDataUrodzenia();
                $age = $today->diff($birthDate)->y;
                $templateData[$prefix . '_wiek'] = $age;
            } else {
                $templateData[$prefix . '_wiek'] = '';
            }
            
            $templateData[$prefix . '_pelnione_funkcje'] = $this->formatUserRoles($user);
        }
    }

    protected function formatUserRoles(User $user): string
    {
        $roles = $user->getRoles();
        $formattedRoles = [];
        
        $roleMapping = [
            'ROLE_PREZES_PARTII' => 'Prezes Partii',
            'ROLE_WICEPREZES_PARTII' => 'Wiceprezes Partii',
            'ROLE_SEKRETARZ_PARTII' => 'Sekretarz Partii',
            'ROLE_SKARBNIK_PARTII' => 'Skarbnik Partii',
            'ROLE_PREZES_OKREGU' => 'Prezes Okręgu',
            'ROLE_PO_PREZES_OKREGU' => 'Pełniący Obowiązki Prezesa Okręgu',
            'ROLE_WICEPREZES_OKREGU' => 'Wiceprezes Okręgu',
            'ROLE_SEKRETARZ_OKREGU' => 'Sekretarz Okręgu',
            'ROLE_SKARBNIK_OKREGU' => 'Skarbnik Okręgu',
            'ROLE_PRZEWODNICZACY_ODDZIALU' => 'Przewodniczący Oddziału',
            'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU' => 'Zastępca Przewodniczącego Oddziału',
            'ROLE_SEKRETARZ_ODDZIALU' => 'Sekretarz Oddziału',
            'ROLE_PELNOMOCNIK_PRZYJMOWANIA' => 'Pełnomocnik ds. Przyjmowania Nowych Członków',
            'ROLE_PREZES_REGIONU' => 'Prezes Regionu',
            'ROLE_SEKRETARZ_REGIONU' => 'Sekretarz Regionu',
            'ROLE_SKARBNIK_REGIONU' => 'Skarbnik Regionu',
            'ROLE_PRZEWODNICZACY_RADY' => 'Przewodniczący Rady Krajowej',
            'ROLE_ZASTEPCA_PRZEWODNICZACY_RADY' => 'Zastępca Przewodniczącego Rady Krajowej',
            'ROLE_PRZEWODNICZACY_KOMISJI_REWIZYJNEJ' => 'Przewodniczący Komisji Rewizyjnej',
            'ROLE_PRZEWODNICZACY_KLUBU' => 'Przewodniczący Klubu Parlamentarnego',
            'ROLE_PRZEWODNICZACY_DELEGACJI' => 'Przewodniczący Delegacji',
            'ROLE_CZLONEK' => 'Członek Partii',
        ];
        
        foreach ($roles as $role) {
            if (isset($roleMapping[$role]) && $role !== 'ROLE_USER') {
                $formattedRoles[] = $roleMapping[$role];
            }
        }

        if (empty($formattedRoles)) {
            $formattedRoles[] = 'Członek Partii';
        }

        return implode(', ', $formattedRoles);
    }

    protected function addSignerDataWithoutFallback(array &$templateData, User $signer): void
    {
        $roles = $signer->getRoles();
        $fullName = $signer->getFullName();

        if (in_array('ROLE_PELNOMOCNIK_PRZYJMOWANIA', $roles)) {
            $templateData['podpisujacy'] = $fullName;
            $templateData['pelnomocnik'] = $fullName;
        }

        if (in_array('ROLE_PREZES_PARTII', $roles)) {
            $templateData['prezes_partii'] = $fullName;
        }

        if (in_array('ROLE_PREZES_OKREGU', $roles)) {
            $templateData['prezes_okregu'] = $fullName;
        }

        if (in_array('ROLE_SEKRETARZ_PARTII', $roles)) {
            $templateData['sekretarz_partii'] = $fullName;
        }

        if (in_array('ROLE_SEKRETARZ_OKREGU', $roles)) {
            $templateData['sekretarz_okregu'] = $fullName;
        }

        if (in_array('ROLE_SKARBNIK_PARTII', $roles)) {
            $templateData['skarbnik_partii'] = $fullName;
        }

        if (in_array('ROLE_SKARBNIK_OKREGU', $roles)) {
            $templateData['skarbnik_okregu'] = $fullName;
        }
    }

    protected function addSignerData(array &$templateData, User $signer): void
    {
        $roles = $signer->getRoles();
        $fullName = $signer->getFullName();

        if (in_array('ROLE_PELNOMOCNIK_PRZYJMOWANIA', $roles)) {
            $templateData['podpisujacy'] = $fullName;
            $templateData['pelnomocnik'] = $fullName;
        }

        if (in_array('ROLE_PREZES_PARTII', $roles)) {
            $templateData['prezes_partii'] = $fullName;
        }

        if (in_array('ROLE_PREZES_OKREGU', $roles)) {
            $templateData['prezes_okregu'] = $fullName;
        }

        if (in_array('ROLE_SEKRETARZ_PARTII', $roles)) {
            $templateData['sekretarz_partii'] = $fullName;
        }

        if (in_array('ROLE_SEKRETARZ_OKREGU', $roles)) {
            $templateData['sekretarz_okregu'] = $fullName;
        }

        if (in_array('ROLE_SKARBNIK_PARTII', $roles)) {
            $templateData['skarbnik_partii'] = $fullName;
        }

        if (in_array('ROLE_SKARBNIK_OKREGU', $roles)) {
            $templateData['skarbnik_okregu'] = $fullName;
        }

        if (!isset($templateData['czlonek_zarzadu'])) {
            $templateData['czlonek_zarzadu'] = $fullName;
        }
    }

    public function fillTemplate(string $template, array $data): string
    {
        return '';
    }

    private function convertValueToString($value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }
        
        if ($value instanceof \DateTime) {
            return $value->format('d.m.Y');
        }
        
        if ($value instanceof User) {
            return $value->getImie() . ' ' . $value->getNazwisko();
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'convertValueToString'], $value));
        }

        return '';
    }

    protected function addSignaturesToTemplate(array &$templateData, Dokument $dokument): void
    {
        $signersConfig = $this->getSignersConfig();
        
        foreach ($dokument->getPodpisy() as $podpis) {
            if ($podpis->isSigned() && $podpis->getPodpisElektroniczny()) {
                $user = $podpis->getPodpisujacy();

                $placeholder = $this->getSignaturePlaceholderForUser($user, $signersConfig);

                if ($placeholder) {
                    $templateData[$placeholder] = $this->generateInlineSignatureHtml(
                        $podpis->getPodpisElektroniczny(),
                        $user->getFullName()
                    );
                }
            }
        }
    }

    private function getSignaturePlaceholderForUser(User $user, array $signersConfig): ?string
    {
        $roles = $user->getRoles();

        if (isset($signersConfig['creator']) && $signersConfig['creator']) {
            if (in_array('ROLE_PREZES_PARTII', $roles)) return 'prezes_partii';
            if (in_array('ROLE_SEKRETARZ_PARTII', $roles)) return 'sekretarz_partii';
            if (in_array('ROLE_SKARBNIK_PARTII', $roles)) return 'skarbnik_partii';
            if (in_array('ROLE_PREZES_OKREGU', $roles)) return 'prezes_okregu';
            if (in_array('ROLE_SEKRETARZ_OKREGU', $roles)) return 'sekretarz_okregu';
            if (in_array('ROLE_SKARBNIK_OKREGU', $roles)) return 'skarbnik_okregu';
        }

        $roleToPlaceholder = [
            'ROLE_PREZES_PARTII' => 'prezes_partii',
            'ROLE_SEKRETARZ_PARTII' => 'sekretarz_partii', 
            'ROLE_SKARBNIK_PARTII' => 'skarbnik_partii',
            'ROLE_PREZES_OKREGU' => 'prezes_okregu',
            'ROLE_SEKRETARZ_OKREGU' => 'sekretarz_okregu',
            'ROLE_SKARBNIK_OKREGU' => 'skarbnik_okregu',
            'ROLE_PRZEWODNICZACY_WALNEGO' => 'przewodniczacy_walnego',
            'ROLE_SEKRETARZ_WALNEGO' => 'sekretarz_walnego',
            'ROLE_PRZEWODNICZACY_RADY' => 'przewodniczacy_rady',
            'ROLE_SEKRETARZ_ZEBRANIA' => 'sekretarz_zebrania',
        ];

        foreach ($roleToPlaceholder as $role => $placeholder) {
            if (in_array($role, $roles) && isset($signersConfig[$placeholder])) {
                return $placeholder;
            }
        }

        return null;
    }

    private function generateInlineSignatureHtml(string $signatureData, string $userName): string
    {
        if (!str_starts_with($signatureData, 'data:image/')) {
            return $userName;
        }

        return sprintf(
            '<div style="text-align: center;"><img src="%s" alt="Podpis %s" style="max-width: 200px; max-height: 50px; margin-bottom: 5px;"><br><strong>%s</strong></div>',
            $signatureData,
            $userName,
            $userName
        );
    }
}