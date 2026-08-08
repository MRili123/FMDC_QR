"""
Extraction de texte d'une pièce jointe, pour le back-office de la FMDC.

Deux services du §8 que le modèle de texte d'Ollama ne sait pas rendre :
  - transcription vocale (darija, arabe, amazighe, français) via faster-whisper ;
  - lecture de documents (OCR) des tickets et factures via Tesseract.

Appelé par Laravel avec un chemin de fichier, il écrit un objet JSON sur la
sortie standard. Tout se passe localement : aucune pièce ne quitte la machine,
ce qui vaut aussi pour les données du §9.

    python extract_text.py --file C:/.../piece.jpg --kind image --lang fr
    -> {"ok": true, "text": "...", "engine": "tesseract", "lang": "fra+ara"}
"""
import argparse
import json
import os
import sys

# Les fichiers de langue de Tesseract vivent dans le projet : « Program Files »
# n'est pas accessible en écriture sans élévation, et le dépôt doit rester
# installable sans droits d'administrateur.
TESSDATA = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                        'storage', 'app', 'tessdata')


def sortie(ok, **champs):
    # Sans cela, Windows écrit dans la page de code de la console (cp1252) : le
    # « é » de « développeur » ressortait mutilé et json_decode rejetait toute la
    # réponse côté PHP. L'arabe serait perdu de la même façon.
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

    print(json.dumps({'ok': ok, **champs}, ensure_ascii=False))
    sys.exit(0 if ok else 1)


def ocr(chemin, langue):
    try:
        import pytesseract
        from PIL import Image
    except ImportError as e:
        sortie(False, error='dependance_manquante', detail=str(e))

    if os.path.isdir(TESSDATA):
        os.environ['TESSDATA_PREFIX'] = TESSDATA

    for candidat in (r'C:\Program Files\Tesseract-OCR\tesseract.exe',
                     r'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe'):
        if os.path.exists(candidat):
            pytesseract.pytesseract.tesseract_cmd = candidat
            break

    # Un ticket marocain mêle couramment français et arabe : on donne les deux
    # à Tesseract plutôt que de deviner, quitte à être un peu plus lent.
    langues = 'ara+fra' if langue == 'ar' else 'fra+ara'

    try:
        with Image.open(chemin) as img:
            if img.mode not in ('L', 'RGB'):
                img = img.convert('RGB')
            texte = pytesseract.image_to_string(img, lang=langues)
    except Exception as e:
        sortie(False, error='ocr_echec', detail=str(e))

    texte = '\n'.join(l.strip() for l in texte.splitlines() if l.strip())
    sortie(True, text=texte, engine='tesseract', lang=langues)


def transcrire(chemin, langue, taille):
    try:
        from faster_whisper import WhisperModel
    except ImportError as e:
        sortie(False, error='dependance_manquante', detail=str(e))

    try:
        # int8 sur CPU : le pilote NVIDIA de la machine est trop ancien pour
        # CUDA, et la transcription reste largement assez rapide ainsi.
        #
        # `local_files_only` d'abord : sinon huggingface_hub ouvre une connexion
        # pour vérifier le modèle, et sous le serveur de développement de PHP
        # l'initialisation de Winsock échoue (WinError 10106). Le modèle est déjà
        # en cache après le premier appel, cette vérification n'apporte rien.
        try:
            modele = WhisperModel(taille, device='cpu', compute_type='int8',
                                  local_files_only=True)
        except Exception:
            # Premier appel : le modèle n'est pas encore en cache, il faut bien
            # le télécharger une fois.
            modele = WhisperModel(taille, device='cpu', compute_type='int8')
        segments, info = modele.transcribe(
            chemin,
            language=None if langue == 'auto' else langue,
            beam_size=5,
            vad_filter=True,
        )
        texte = ' '.join(s.text.strip() for s in segments).strip()
    except Exception as e:
        sortie(False, error='transcription_echec', detail=str(e))

    sortie(True, text=texte, engine=f'faster-whisper:{taille}',
           lang=getattr(info, 'language', langue) or langue)


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--file', required=True)
    p.add_argument('--kind', required=True, choices=['image', 'audio'])
    p.add_argument('--lang', default='fr')
    p.add_argument('--model', default='small')
    a = p.parse_args()

    if not os.path.isfile(a.file):
        sortie(False, error='fichier_introuvable', detail=a.file)

    if a.kind == 'image':
        ocr(a.file, a.lang)
    else:
        transcrire(a.file, a.lang, a.model)


if __name__ == '__main__':
    main()
