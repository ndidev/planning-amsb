<?php

// Path: api/src/DTO/PDF/PlanningPDF.php

declare(strict_types=1);

namespace App\DTO\PDF;

use App\Entity\Config\AgencyDepartment;
use App\Entity\ThirdParty;

define("_SYSTEM_TTFONTS", UNIFONTS . "/");

abstract class PlanningPDF extends \tFPDF
{
    public function __construct(
        protected ThirdParty $supplier,
        protected AgencyDepartment $agencyDepartment
    ) {
        parent::__construct('P', 'mm', 'A4');

        // Ajout d'une police compatible UTF-8
        $this->AddFont("Roboto", "", "Roboto-Regular.ttf", true);
        $this->AddFont("RobotoB", "", "Roboto-Bold.ttf", true);
        $this->AddFont("RobotoI", "", "Roboto-Italic.ttf", true);
    }

    abstract protected function generatePDF(): void;

    /**
     * Constrution de l'en-tête du PDF.
     */
    function Header(): void
    {
        // Logo
        $logoFilename = $this->supplier->getLogoFilename();
        if ($logoFilename && file_exists(LOGOS . "/" . $logoFilename)) {
            $this->includeLogo(LOGOS . "/" . $logoFilename);
        }
        // Police Arial gras 15
        $this->SetFont('RobotoB', '', 15);
        // Couleur
        $this->SetTextColor(0, 0, 0);
        // Décalage à droite
        $this->Cell(70);
        // Titre
        $this->Cell(70, 10, "Planning {$this->supplier->getShortName()} ({$this->agencyDepartment->getCity()})", 'B', 0, 'C');
        // Saut de ligne
        $this->Ln(20);
    }

    /**
     * Constrution du pied de page du PDF.
     */
    function Footer(): void
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('RobotoI', '', 8);
        // Couleur
        $this->SetTextColor(0, 0, 0);
        // Numéro de page
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    /**
     * Renvoie un PDF sous forme `string` pour visualisation (téléchargement).
     */
    public function stringifyPDF(): string
    {
        return $this->Output('S');
    }

    /**
     * Prise en charge des images WebP.
     * 
     * @param string $file Chemin du fichier WebP.
     * 
     * @return array{
     *           w: mixed,
     *           h: mixed,
     *           cs: string,
     *           bpc: int,
     *           f: string,
     *           dp: string,
     *           pal: string,
     *           trns: string|int[],
     *           smask: string|false,
     *           data: string|false
     *         }
     */
    protected function _parsewebp(string $file): array
    {
        // Extract info from a WebP file (via PNG conversion)
        if (!function_exists('imagepng')) {
            $this->Error('GD extension is required for WebP support');
        }

        if (!function_exists('imagecreatefromwebp')) {
            $this->Error('GD has no WebP support');
        }

        $pngImage = imagecreatefromwebp($file);

        if (!$pngImage) {
            $this->Error('Missing or incorrect image file: ' . $file);
        }

        /** @var \GdImage $pngImage */

        ob_start();
        imagepng($pngImage);
        $pngData = ob_get_clean();
        imagedestroy($pngImage);

        if (!$pngData) {
            $this->Error('Unable to create PNG data');
        }

        /** @var string $pngData */

        $fileHandler = fopen('php://temp', 'rb+');

        if (!$fileHandler) {
            $this->Error('Unable to create memory stream');
        }

        /** @var resource $fileHandler */

        fwrite($fileHandler, $pngData);
        rewind($fileHandler);
        $info = $this->_parsepngstream($fileHandler, $file);
        fclose($fileHandler);

        return $info;
    }

    /**
     * Inclusion du logo du fournisseur.
     * 
     * @param string $logoFilename Nom du fichier du logo.
     */
    protected function includeLogo(string $logoFilename): void
    {
        // Si nécessaire, redimmensionnement pour ne pas être trop large ou trop haute
        $tmp = imagecreatefromwebp($logoFilename);
        if (!$tmp) {
            return;
        }

        $imageSizeInfo = getimagesize($logoFilename);
        if (!$imageSizeInfo) {
            return;
        }
        [$width_px, $height_px] = $imageSizeInfo;

        /** @var array{0: int, 1: int}|false */
        $imageResolutionInfo = imageresolution($tmp);
        if (!$imageResolutionInfo) {
            return;
        }
        [$res_w, $res_h] = $imageResolutionInfo;

        $ratio = $width_px / $height_px;

        if (!defined("MM_PER_INCH")) define("MM_PER_INCH", 25.4);
        if (!defined("MAX_WIDTH")) define("MAX_WIDTH", 50); // Largeur maximale de l'image sur le PDF (en mm)
        if (!defined("MAX_HEIGHT")) define("MAX_HEIGHT", 20); // Hauteur maximale de l'image sur le PDF (en mm)

        $original_width_mm = $width_px / $res_w * MM_PER_INCH;
        $original_height_mm = $height_px / $res_h * MM_PER_INCH;

        // D'abord, réduction de la largeur (si nécessaire)
        $pdf_width_mm = min($original_width_mm, MAX_WIDTH);
        $pdf_height_mm = $pdf_width_mm / $ratio;

        // Ensuite, réduction de la hauteur (si nécessaire)
        $pdf_height_mm = min($pdf_height_mm, MAX_HEIGHT);
        $pdf_width_mm = $pdf_height_mm * $ratio;

        $this->Image(LOGOS . "/" . $this->supplier->getLogoFilename(), 10, 6, $pdf_width_mm, $pdf_height_mm);
    }
}
