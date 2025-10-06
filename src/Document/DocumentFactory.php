<?php

namespace App\Document;

use App\Entity\Dokument;
use App\Document\Czlonkostwo\PrzyjecieCzlonkaPelnomocnik;
use App\Document\Czlonkostwo\PrzyjecieCzlonkaOkreg;
use App\Document\Czlonkostwo\PrzyjecieCzlonkaKrajowy;
use App\Document\Czlonkostwo\OswiadczenieWystapienia;
use App\Document\Czlonkostwo\UchwalaSkresleniaCzlonka;
use App\Document\Czlonkostwo\WniosekZawieszeniaGzlonkostwa;
use App\Document\Powolania\PowolaniePelnomocnikStruktur;
use App\Document\Powolania\PowolanieSekretarzPartii;
use App\Document\Powolania\PowolanieSKarbnikPartii;
use App\Document\Powolania\PowolanieWiceprezesPartii;
use App\Document\Powolania\PowolaniePOPrezesOkregu;
use App\Document\Powolania\PowolanieSekretarzOkregu;
use App\Document\Powolania\PowolanieSkarbnikOkregu;
use App\Document\Powolania\WyznaczenieOsobyTymczasowej;
use App\Document\Powolania\PowolaniePrezesRegionu;
use App\Document\Odwolania\OdwolanieSekretarzPartii;
use App\Document\Odwolania\OdwolaniePelnomocnikStruktur;
use App\Document\Odwolania\OdwolanieSkarbnikPartii;
use App\Document\Odwolania\OdwolanieWiceprezesPartii;
use App\Document\Odwolania\OdwolaniePrezesOkregu;
use App\Document\Odwolania\OdwolaniePOPrezesOkregu;
use App\Document\Odwolania\OdwolanieSekretarzOkregu;
use App\Document\Odwolania\OdwolanieSkarbnikOkregu;
use App\Document\Odwolania\OdwolaniePrezesRegionu;
use App\Document\Struktura\UtworzenieOddzialu;
use App\Document\Zebrania\WyznaczenieObserwatora;
use App\Document\Zebrania\WyznaczenieProtokolanta;
use App\Document\Zebrania\WyznaczenieProwadzacego;
use App\Document\Zebrania\PowolaniePrzewodniczacegoOddzialu;
use App\Document\Zebrania\OdwolaniePrzewodniczacegoOddzialu;
use App\Document\Zebrania\PowolanieZastepcy;
use App\Document\Zebrania\OdwolanieZastepcy;
use App\Document\Zebrania\PowolanieSekretarzaOddzialu;
use App\Document\Zebrania\OdwolanieSekretarzaOddzialu;
use App\Document\Zebrania\WyborPrezesaOkreguWalne;
use App\Document\Zebrania\WyborWiceprezesaOkreguWalne;
use App\Document\Zebrania\WyborSekretarzaOkreguWalne;
use App\Document\Zebrania\WyborSkarbnikaOkreguWalne;
use App\Document\Zebrania\WyborSekretarzaRegionu;
use App\Document\Zebrania\WyborSkarbnikaRegionu;
use App\Document\Zebrania\WyborPrzewodniczacegoRady;
use App\Document\Zebrania\WyborZastepcyPrzewodniczacegoRady;
use App\Document\Zebrania\WyborPrzewodniczacegoKomisjiRewizyjnej;
use App\Document\Zebrania\PowolaniePrzewodniczacegoKlubu;
use App\Document\Zebrania\WyborPrzewodniczacegoDelegacji;
use App\Document\Dyscyplinarne\PostanowienieSaduPartyjnego;
use App\Document\Rezygnacje\RezygnacjaZFunkcji;

class DocumentFactory
{
    private const DOCUMENT_MAP = [
        Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => PrzyjecieCzlonkaPelnomocnik::class,
        Dokument::TYP_PRZYJECIE_CZLONKA_OKREG => PrzyjecieCzlonkaOkreg::class,
        Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY => PrzyjecieCzlonkaKrajowy::class,
        Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR => PowolaniePelnomocnikStruktur::class,
        Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII => PowolanieSekretarzPartii::class,
        Dokument::TYP_POWOLANIE_SKARBNIK_PARTII => PowolanieSKarbnikPartii::class,
        Dokument::TYP_POWOLANIE_WICEPREZES_PARTII => PowolanieWiceprezesPartii::class,
        Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU => PowolaniePOPrezesOkregu::class,
        Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU => PowolanieSekretarzOkregu::class,
        Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU => PowolanieSkarbnikOkregu::class,
        Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII => OdwolanieSekretarzPartii::class,
        Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR => OdwolaniePelnomocnikStruktur::class,
        Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII => OdwolanieSkarbnikPartii::class,
        Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII => OdwolanieWiceprezesPartii::class,
        Dokument::TYP_ODWOLANIE_PREZES_OKREGU => OdwolaniePrezesOkregu::class,
        Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU => OdwolaniePOPrezesOkregu::class,
        Dokument::TYP_ODWOLANIE_SEKRETARZ_OKREGU => OdwolanieSekretarzOkregu::class,
        Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU => OdwolanieSkarbnikOkregu::class,
        Dokument::TYP_UTWORZENIE_ODDZIALU => UtworzenieOddzialu::class,
        Dokument::TYP_WYZNACZENIE_OBSERWATORA => WyznaczenieObserwatora::class,
        Dokument::TYP_WYZNACZENIE_PROTOKOLANTA => WyznaczenieProtokolanta::class,
        Dokument::TYP_WYZNACZENIE_PROWADZACEGO => WyznaczenieProwadzacego::class,
        Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU => PowolaniePrzewodniczacegoOddzialu::class,
        Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU => OdwolaniePrzewodniczacegoOddzialu::class,
        Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => PowolanieZastepcy::class,
        Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => OdwolanieZastepcy::class,
        Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU => PowolanieSekretarzaOddzialu::class,
        Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU => OdwolanieSekretarzaOddzialu::class,
        Dokument::TYP_WYBOR_PREZESA_OKREGU_WALNE => WyborPrezesaOkreguWalne::class,
        Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE => WyborWiceprezesaOkreguWalne::class,
        Dokument::TYP_WYBOR_SEKRETARZA_OKREGU_WALNE => WyborSekretarzaOkreguWalne::class,
        Dokument::TYP_WYBOR_SKARBNIKA_OKREGU_WALNE => WyborSkarbnikaOkreguWalne::class,
        Dokument::TYP_WYZNACZENIE_OSOBY_TYMCZASOWEJ => WyznaczenieOsobyTymczasowej::class,
        Dokument::TYP_POWOLANIE_PREZES_REGIONU => PowolaniePrezesRegionu::class,
        Dokument::TYP_ODWOLANIE_PREZES_REGIONU => OdwolaniePrezesRegionu::class,
        Dokument::TYP_WYBOR_SEKRETARZ_REGIONU => WyborSekretarzaRegionu::class,
        Dokument::TYP_WYBOR_SKARBNIK_REGIONU => WyborSkarbnikaRegionu::class,
        Dokument::TYP_WYBOR_PRZEWODNICZACY_RADY => WyborPrzewodniczacegoRady::class,
        Dokument::TYP_WYBOR_ZASTEPCA_PRZEWODNICZACY_RADY => WyborZastepcyPrzewodniczacegoRady::class,
        Dokument::TYP_WYBOR_PRZEWODNICZACY_KOMISJI_REWIZYJNEJ => WyborPrzewodniczacegoKomisjiRewizyjnej::class,
        Dokument::TYP_POWOLANIE_PRZEWODNICZACY_KLUBU => PowolaniePrzewodniczacegoKlubu::class,
        Dokument::TYP_WYBOR_PRZEWODNICZACY_DELEGACJI => WyborPrzewodniczacegoDelegacji::class,
        Dokument::TYP_OSWIADCZENIE_WYSTAPIENIA => OswiadczenieWystapienia::class,
        Dokument::TYP_UCHWALA_SKRESLENIA_CZLONKA => UchwalaSkresleniaCzlonka::class,
        Dokument::TYP_WNIOSEK_ZAWIESZENIA_CZLONKOSTWA => WniosekZawieszeniaGzlonkostwa::class,
        Dokument::TYP_POSTANOWIENIE_SADU_PARTYJNEGO => PostanowienieSaduPartyjnego::class,
        Dokument::TYP_REZYGNACJA_Z_FUNKCJI => RezygnacjaZFunkcji::class,
    ];

    public static function create(string $type): AbstractDocument
    {
        if (!isset(self::DOCUMENT_MAP[$type])) {
            throw new \InvalidArgumentException(sprintf('Nieznany typ dokumentu: %s', $type));
        }

        $className = self::DOCUMENT_MAP[$type];

        if (!class_exists($className)) {
            throw new \RuntimeException(sprintf('Klasa dokumentu nie istnieje: %s', $className));
        }

        return new $className();
    }

    public static function isSupported(string $type): bool
    {
        return isset(self::DOCUMENT_MAP[$type]);
    }

    public static function getSupportedTypes(): array
    {
        return array_keys(self::DOCUMENT_MAP);
    }

    public static function getDocumentMap(): array
    {
        return self::DOCUMENT_MAP;
    }

    public static function generateContent(string $type, Dokument $dokument, array $data): string
    {
        return '';
    }

    public static function getDocumentInfo(string $type): array
    {
        $document = self::create($type);

        return [
            'type' => $document->getType(),
            'title' => $document->getTitle(),
            'category' => $document->getCategory(),
            'description' => $document->getDescription(),
            'requiredFields' => $document->getRequiredFields(),
            'signersConfig' => $document->getSignersConfig(),
        ];
    }
}
