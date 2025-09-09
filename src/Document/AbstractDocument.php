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
    
    /**
     * Zwraca typ dokumentu (stała z encji Dokument)
     */
    abstract public function getType(): string;
    
    /**
     * Zwraca tytuł dokumentu
     */
    abstract public function getTitle(): string;
    
    /**
     * Zwraca kategorię dokumentu
     */
    abstract public function getCategory(): string;
    
    /**
     * Zwraca opis dokumentu
     */
    abstract public function getDescription(): string;
    
    /**
     * Generuje treść dokumentu na podstawie danych
     */
    abstract public function generateContent(array $data): string;
    
    /**
     * Zwraca wymagane pola dla dokumentu
     */
    abstract public function getRequiredFields(): array;
    
    /**
     * Zwraca konfigurację podpisujących
     */
    abstract public function getSignersConfig(): array;
    
    /**
     * Waliduje dane przed utworzeniem dokumentu
     */
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
    
    /**
     * Przygotowuje dane szablonu
     */
    public function prepareTemplateData(Dokument $dokument, array $data): array
    {
        $templateData = [];
        
        // Podstawowe dane dokumentu
        $templateData['numer_dokumentu'] = $dokument->getNumerDokumentu();
        $templateData['data'] = date('d.m.Y');
        $templateData['data_wejscia'] = $dokument->getDataWejsciaWZycie()->format('d.m.Y');
        
        // Dane okręgu
        if ($dokument->getOkreg()) {
            $templateData['okreg'] = $dokument->getOkreg()->getNazwa();
        }
        
        // Dane kandydata/członka - pobierz z encji dokumentu, nie z surowych danych
        if ($dokument->getKandydat()) {
            $this->addUserData($templateData, $dokument->getKandydat(), '');
        } elseif ($dokument->getCzlonek()) {
            $this->addUserData($templateData, $dokument->getCzlonek(), '');
        }
        
        // Fallback do surowych danych jeśli są obiektami User
        if (isset($data['kandydat']) && $data['kandydat'] instanceof User) {
            $this->addUserData($templateData, $data['kandydat'], 'kandydat');
        }
        
        if (isset($data['czlonek']) && $data['czlonek'] instanceof User) {
            $this->addUserData($templateData, $data['czlonek'], 'czlonek');
        }
        
        if (isset($data['powolywan_czlonek']) && $data['powolywan_czlonek'] instanceof User) {
            $this->addUserData($templateData, $data['powolywan_czlonek'], 'powolywan');
        }
        
        if (isset($data['odwolywan_czlonek']) && $data['odwolywan_czlonek'] instanceof User) {
            $this->addUserData($templateData, $data['odwolywan_czlonek'], 'odwolywan');
        }
        
        // Dane podpisujących
        if ($dokument->getTworca()) {
            $this->addSignerData($templateData, $dokument->getTworca());
        }
        
        if (isset($data['drugi_podpisujacy']) && $data['drugi_podpisujacy'] instanceof User) {
            $this->addSignerData($templateData, $data['drugi_podpisujacy']);
        }
        
        // Dodaj podpisy elektroniczne z dokumentu
        $this->addSignaturesToTemplate($templateData, $dokument);
        
        // Dane dodatkowe
        if ($daneDodatkowe = $dokument->getDaneDodatkowe()) {
            $templateData = array_merge($templateData, $daneDodatkowe);
        }
        
        return $templateData;
    }
    
    /**
     * Dodaje dane użytkownika do szablonu
     */
    protected function addUserData(array &$templateData, User $user, string $prefix = ''): void
    {
        // Tylko podstawowe dane członka
        $templateData['imie_nazwisko'] = $user->getFullName();
        $templateData['user_id'] = $user->getId();
        $templateData['numer_w_partii'] = $user->getId(); // Używamy ID jako numer w partii
        
        if ($prefix) {
            $templateData[$prefix . '_imie_nazwisko'] = $user->getFullName();
            $templateData[$prefix . '_user_id'] = $user->getId();
            $templateData[$prefix . '_numer_w_partii'] = $user->getId();
        }
    }
    
    /**
     * Dodaje dane podpisującego do szablonu
     */
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
        
        // Domyślnie jako członek zarządu
        if (!isset($templateData['czlonek_zarzadu'])) {
            $templateData['czlonek_zarzadu'] = $fullName;
        }
    }
    
    /**
     * Wypełnia szablon danymi
     */
    public function fillTemplate(string $template, array $data): string
    {
        $content = $template;
        foreach ($data as $key => $value) {
            // Konwertuj wartość na string
            $stringValue = $this->convertValueToString($value);
            $content = str_replace('{' . $key . '}', $stringValue, $content);
        }
        return $content;
    }
    
    /**
     * Konwertuje wartość na string
     */
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
        
        // Dla innych obiektów spróbuj użyć __toString lub po prostu return empty string
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'convertValueToString'], $value));
        }
        
        // Fallback
        return '';
    }
    
    /**
     * Dodaje podpisy elektroniczne do szablonu
     */
    protected function addSignaturesToTemplate(array &$templateData, Dokument $dokument): void
    {
        $signersConfig = $this->getSignersConfig();
        
        foreach ($dokument->getPodpisy() as $podpis) {
            if ($podpis->isSigned() && $podpis->getPodpisElektroniczny()) {
                $user = $podpis->getPodpisujacy();
                
                // Mapuj użytkowników na placeholdery w szablonach na podstawie ich ról
                $placeholder = $this->getSignaturePlaceholderForUser($user, $signersConfig);
                
                if ($placeholder) {
                    // Wstaw HTML z podpisem elektronicznym
                    $templateData[$placeholder] = $this->generateInlineSignatureHtml(
                        $podpis->getPodpisElektroniczny(),
                        $user->getFullName()
                    );
                }
            }
        }
    }
    
    /**
     * Mapuje użytkownika na odpowiedni placeholder podpisu
     */
    private function getSignaturePlaceholderForUser(User $user, array $signersConfig): ?string
    {
        $roles = $user->getRoles();
        
        // Specjalne mapowanie dla creator (twórca dokumentu)
        if (isset($signersConfig['creator']) && $signersConfig['creator']) {
            // Mapuj na podstawie najwyższej roli twórcy
            if (in_array('ROLE_PREZES_PARTII', $roles)) return 'prezes_partii';
            if (in_array('ROLE_SEKRETARZ_PARTII', $roles)) return 'sekretarz_partii';
            if (in_array('ROLE_SKARBNIK_PARTII', $roles)) return 'skarbnik_partii';
            if (in_array('ROLE_PREZES_OKREGU', $roles)) return 'prezes_okregu';
            if (in_array('ROLE_SEKRETARZ_OKREGU', $roles)) return 'sekretarz_okregu';
            if (in_array('ROLE_SKARBNIK_OKREGU', $roles)) return 'skarbnik_okregu';
        }
        
        // Mapowanie ról na placeholdery
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
        
        // Znajdź pierwszy pasujący placeholder
        foreach ($roleToPlaceholder as $role => $placeholder) {
            if (in_array($role, $roles) && isset($signersConfig[$placeholder])) {
                return $placeholder;
            }
        }
        
        return null;
    }
    
    /**
     * Generuje inline HTML z podpisem elektronicznym
     */
    private function generateInlineSignatureHtml(string $signatureData, string $userName): string
    {
        // Sprawdź czy to już jest data URL
        if (!str_starts_with($signatureData, 'data:image/')) {
            return $userName; // Fallback do samej nazwy użytkownika
        }
        
        return sprintf(
            '<div style="text-align: center;"><img src="%s" alt="Podpis %s" style="max-width: 200px; max-height: 50px; margin-bottom: 5px;"><br><strong>%s</strong></div>',
            $signatureData,
            $userName,
            $userName
        );
    }
}