<?php

namespace Api\Utils\PDF;

use \Exception;
use \tFPDF;

define("_SYSTEM_TTFONTS", UNIFONTS . "/");

class PDFPlanning extends tFPDF
{
  public function __construct(
    protected array $fournisseur,
    protected array $agence
  ) {
    parent::__construct('P', 'mm', 'A4');

    // Ajout d'une police compatible UTF-8
    $this->AddFont("Roboto", "", "Roboto-Regular.ttf", true);
    $this->AddFont("RobotoB", "", "Roboto-Bold.ttf", true);
    $this->AddFont("RobotoI", "", "Roboto-Italic.ttf", true);
  }

  /**
   * Constrution de l'en-tête du PDF.
   */
  function Header(): void
  {
    // Logo
    if ($this->fournisseur["logo"] && file_exists(LOGOS . "/" . $this->fournisseur["logo"])) {
      // Si nécessaire, redimmensionnement pour ne pas être trop large ou trop haute
      $tmp = imagecreatefromwebp(LOGOS . "/" . $this->fournisseur["logo"]);
      [$width_px, $height_px] = getimagesize(LOGOS . "/" . $this->fournisseur["logo"]);
      [$res_w, $res_h] = imageresolution($tmp);

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

      $this->Image(LOGOS . "/" . $this->fournisseur["logo"], 10, 6, $pdf_width_mm, $pdf_height_mm);
    }
    // Police Arial gras 15
    $this->SetFont('RobotoB', '', 15);
    // Couleur
    $this->SetTextColor(0, 0, 0);
    // Décalage à droite
    $this->Cell(70);
    // Titre
    $this->Cell(70, 10, "Planning {$this->fournisseur['nom']} ({$this->agence['ville']})", 'B', 0, 'C');
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
   * Prise en charge des images WebP.
   * 
   * @param string $file Chemin du fichier WebP.
   * 
   * @return array 
   * @throws Exception 
   */
  protected function _parsewebp(string $file): array
  {
    // Extract info from a WebP file (via PNG conversion)
    if (!function_exists('imagepng'))
      $this->Error('GD extension is required for WebP support');

    if (!function_exists('imagecreatefromwebp'))
      $this->Error('GD has no WebP support');

    $png_image = imagecreatefromwebp($file);

    if (!$png_image)
      $this->Error('Missing or incorrect image file: ' . $file);

    ob_start();
    imagepng($png_image);
    $png_data = ob_get_clean();
    imagedestroy($png_image);
    $f = fopen('php://temp', 'rb+');

    if (!$f)
      $this->Error('Unable to create memory stream');

    fwrite($f, $png_data);
    rewind($f);
    $info = $this->_parsepngstream($f, $file);
    fclose($f);

    return $info;
  }
}
