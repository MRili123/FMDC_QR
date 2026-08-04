"use client";

import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { useLocale } from "@/i18n/client";
import { CATEGORIES, CATEGORY_ICONS, MOTIFS, RESULTATS } from "@/lib/taxonomy";
import {
  clearDraft,
  emptyDraft,
  loadDraft,
  saveDraft,
  type DraftAttachment,
  type WizardDraft,
} from "@/lib/draft";
import { ChoiceCard, PrimaryButton, StepShell } from "./wizard-ui";

const TOTAL_STEPS = 7;
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

export interface WizardContext {
  qrCode?: string;
  etablissement?: string;
  region?: string;
  secteur?: string;
}

/** Après une coupure réseau, on rouvre le brouillon là où il s'est arrêté plutôt qu'à l'écran 1. */
function resumeStep(draft: WizardDraft): number {
  if (draft.resultatAttendu) return 6;
  if (draft.description?.trim() || draft.attachments.length > 0) return 4;
  if (draft.motif) return 3;
  if (draft.categorie) return 2;
  return 1;
}

export function Wizard({ context }: { context?: WizardContext }) {
  const { locale, t } = useLocale();
  const router = useRouter();

  const [draft, setDraft] = useState<WizardDraft | null>(null);
  const [step, setStep] = useState(1);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  useEffect(() => {
    const loaded = loadDraft();
    setDraft({
      ...loaded,
      qrCode: context?.qrCode ?? loaded.qrCode,
      etablissement: context?.etablissement ?? loaded.etablissement,
      region: context?.region ?? loaded.region,
    });
    setStep(resumeStep(loaded));
  }, [context?.qrCode, context?.etablissement, context?.region]);

  useEffect(() => {
    if (draft) saveDraft(draft);
  }, [draft]);

  if (!draft) {
    return <p className="mx-auto max-w-2xl px-4 py-8 text-muted">{t("common.loading")}</p>;
  }

  const update = (patch: Partial<WizardDraft>) =>
    setDraft((current) => (current ? { ...current, ...patch } : current));

  const back = () => setStep((value) => Math.max(1, value - 1));

  async function submit(contact: ContactPayload) {
    setSubmitting(true);
    setSubmitError(null);
    try {
      const response = await fetch("/api/dossiers", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          draftId: draft!.draftId,
          demarche: contact.demarche,
          categorie: draft!.categorie,
          motif: draft!.motif,
          description: draft!.description,
          resultatAttendu: draft!.resultatAttendu,
          professionnel: draft!.professionnel,
          etablissement: draft!.etablissement,
          region: draft!.region,
          qrCode: draft!.qrCode,
          contact: contact.anonymous
            ? null
            : {
                telephone: contact.telephone || null,
                email: contact.email || null,
                nom: contact.nom || null,
                phoneVerified: contact.phoneVerified,
              },
        }),
      });

      if (!response.ok) throw new Error("submit failed");
      const result = (await response.json()) as { reference: string; token: string };
      clearDraft();
      router.push(
        `/${locale}/reclamation/confirmation?ref=${encodeURIComponent(result.reference)}&t=${encodeURIComponent(result.token)}`,
      );
    } catch {
      setSubmitError(t("common.error"));
      setSubmitting(false);
    }
  }

  if (step === 1) {
    return (
      <StepShell step={1} total={TOTAL_STEPS} title={t("wizard.step1.title")} hint={t("wizard.step1.hint")}>
        <div className="grid gap-2.5 sm:grid-cols-2">
          {CATEGORIES.map((categorie) => (
            <ChoiceCard
              key={categorie}
              icon={CATEGORY_ICONS[categorie]}
              label={t(`categorie.${categorie}`)}
              selected={draft.categorie === categorie}
              onClick={() => {
                update({ categorie });
                setStep(2);
              }}
            />
          ))}
        </div>
      </StepShell>
    );
  }

  if (step === 2) {
    return (
      <StepShell
        step={2}
        total={TOTAL_STEPS}
        title={t("wizard.step2.title")}
        hint={t("wizard.step2.hint")}
        onBack={back}
      >
        <div className="grid gap-2.5">
          {MOTIFS.map((motif) => (
            <ChoiceCard
              key={motif}
              label={t(`motif.${motif}`)}
              selected={draft.motif === motif}
              onClick={() => {
                update({ motif });
                setStep(3);
              }}
            />
          ))}
        </div>
      </StepShell>
    );
  }

  if (step === 3) {
    return (
      <DescribeStep
        draft={draft}
        onBack={back}
        onChange={update}
        onNext={() => setStep(4)}
      />
    );
  }

  if (step === 4) {
    return <EvidenceStep draft={draft} onBack={back} onChange={update} onNext={() => setStep(5)} />;
  }

  if (step === 5) {
    return (
      <StepShell
        step={5}
        total={TOTAL_STEPS}
        title={t("wizard.step5.title")}
        hint={t("wizard.step5.hint")}
        onBack={back}
      >
        <div className="grid gap-2.5">
          {RESULTATS.map((resultat) => (
            <ChoiceCard
              key={resultat}
              label={t(`resultat.${resultat}`)}
              selected={draft.resultatAttendu === resultat}
              onClick={() => {
                update({ resultatAttendu: resultat });
                setStep(6);
              }}
            />
          ))}
        </div>
      </StepShell>
    );
  }

  return (
    <ContactStep
      onBack={back}
      submitting={submitting}
      error={submitError}
      onSubmit={submit}
    />
  );
}

function DescribeStep({
  draft,
  onBack,
  onChange,
  onNext,
}: {
  draft: WizardDraft;
  onBack: () => void;
  onChange: (patch: Partial<WizardDraft>) => void;
  onNext: () => void;
}) {
  const { t } = useLocale();
  const [mode, setMode] = useState<"write" | "voice">("write");
  const [recording, setRecording] = useState(false);
  const [micError, setMicError] = useState(false);
  const recorderRef = useRef<MediaRecorder | null>(null);

  const audio = draft.attachments.find((item) => item.kind === "AUDIO");

  async function startRecording() {
    setMicError(false);
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const recorder = new MediaRecorder(stream);
      const chunks: BlobPart[] = [];
      recorder.ondataavailable = (event) => chunks.push(event.data);
      recorder.onstop = async () => {
        stream.getTracks().forEach((track) => track.stop());
        const blob = new Blob(chunks, { type: recorder.mimeType || "audio/webm" });
        const uploaded = await uploadFile(
          new File([blob], "message-vocal.webm", { type: blob.type }),
          draft.draftId,
          "AUDIO",
        );
        if (uploaded) {
          onChange({
            attachments: [
              ...draft.attachments.filter((item) => item.kind !== "AUDIO"),
              uploaded,
            ],
          });
        }
      };
      recorderRef.current = recorder;
      recorder.start();
      setRecording(true);
    } catch {
      setMicError(true);
    }
  }

  function stopRecording() {
    recorderRef.current?.stop();
    recorderRef.current = null;
    setRecording(false);
  }

  const canContinue = Boolean(draft.description?.trim()) || Boolean(audio);

  return (
    <StepShell
      step={3}
      total={TOTAL_STEPS}
      title={t("wizard.step3.title")}
      hint={t("wizard.step3.hint")}
      onBack={onBack}
      footer={
        <PrimaryButton onClick={onNext} disabled={!canContinue}>
          {t("wizard.next")}
        </PrimaryButton>
      }
    >
      <div className="mb-4 flex gap-2" role="tablist">
        <button
          type="button"
          role="tab"
          aria-selected={mode === "write"}
          onClick={() => setMode("write")}
          className={`flex-1 rounded-lg border-2 px-4 py-2.5 font-medium ${
            mode === "write" ? "border-primary bg-primary-soft text-primary" : "border-line bg-surface"
          }`}
        >
          {t("wizard.step3.tabWrite")}
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={mode === "voice"}
          onClick={() => setMode("voice")}
          className={`flex-1 rounded-lg border-2 px-4 py-2.5 font-medium ${
            mode === "voice" ? "border-primary bg-primary-soft text-primary" : "border-line bg-surface"
          }`}
        >
          {t("wizard.step3.tabVoice")}
        </button>
      </div>

      {mode === "write" ? (
        <textarea
          value={draft.description ?? ""}
          onChange={(event) => onChange({ description: event.target.value })}
          placeholder={t("wizard.step3.placeholder")}
          rows={6}
          className="w-full rounded-xl border-2 border-line bg-surface p-3 text-base"
        />
      ) : (
        <div className="rounded-xl border-2 border-line bg-surface p-5 text-center">
          {audio ? (
            <div className="space-y-3">
              <p className="font-medium text-success">✓ {t("wizard.step3.recorded")}</p>
              <button
                type="button"
                onClick={() => {
                  onChange({
                    attachments: draft.attachments.filter((item) => item.kind !== "AUDIO"),
                  });
                }}
                className="text-sm font-medium text-primary underline"
              >
                {t("wizard.step3.rerecord")}
              </button>
            </div>
          ) : (
            <button
              type="button"
              onClick={recording ? stopRecording : startRecording}
              className={`rounded-full px-6 py-4 text-lg font-semibold text-white ${
                recording ? "bg-danger" : "bg-primary"
              }`}
            >
              {recording ? `⏹ ${t("wizard.step3.stop")}` : `🎤 ${t("wizard.step3.record")}`}
            </button>
          )}
          {recording ? <p className="mt-3 text-muted">{t("wizard.step3.recording")}</p> : null}
          {micError ? <p className="mt-3 text-danger">{t("wizard.step3.micDenied")}</p> : null}
        </div>
      )}

      <label className="mt-5 block">
        <span className="text-sm font-medium">{t("wizard.step3.professionnel")}</span>
        <input
          type="text"
          value={draft.professionnel ?? ""}
          onChange={(event) => onChange({ professionnel: event.target.value })}
          className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3"
        />
        <span className="mt-1 block text-xs text-muted">{t("wizard.step3.professionnelHint")}</span>
      </label>
    </StepShell>
  );
}

function EvidenceStep({
  draft,
  onBack,
  onChange,
  onNext,
}: {
  draft: WizardDraft;
  onBack: () => void;
  onChange: (patch: Partial<WizardDraft>) => void;
  onNext: () => void;
}) {
  const { t } = useLocale();
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const visible = draft.attachments.filter((item) => item.kind !== "AUDIO");

  async function handleFiles(files: FileList | null) {
    if (!files?.length) return;
    setError(null);
    setUploading(true);
    const added: DraftAttachment[] = [];
    for (const file of Array.from(files)) {
      if (file.size > MAX_UPLOAD_BYTES) {
        setError(t("wizard.step4.tooLarge"));
        continue;
      }
      const uploaded = await uploadFile(file, draft.draftId, "AUTRE");
      if (uploaded) added.push(uploaded);
      else setError(t("wizard.step4.failed"));
    }
    onChange({ attachments: [...draft.attachments, ...added] });
    setUploading(false);
    if (inputRef.current) inputRef.current.value = "";
  }

  return (
    <StepShell
      step={4}
      total={TOTAL_STEPS}
      title={t("wizard.step4.title")}
      hint={t("wizard.step4.hint")}
      onBack={onBack}
      footer={<PrimaryButton onClick={onNext}>{t("wizard.next")}</PrimaryButton>}
    >
      <input
        ref={inputRef}
        type="file"
        accept="image/*,application/pdf"
        multiple
        onChange={(event) => handleFiles(event.target.files)}
        className="hidden"
        id="evidence-input"
      />
      <label
        htmlFor="evidence-input"
        role="button"
        className="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary bg-primary-soft px-4 py-6 font-semibold text-primary"
      >
        <span aria-hidden>📎</span>
        {uploading ? t("wizard.step4.uploading") : t("wizard.step4.add")}
      </label>

      {error ? <p className="mt-3 text-danger">{error}</p> : null}

      {visible.length > 0 ? (
        <ul className="mt-4 space-y-2">
          {visible.map((item) => (
            <li
              key={item.id}
              className="flex items-center gap-3 rounded-lg border border-line bg-surface p-3"
            >
              <span aria-hidden>📄</span>
              <span className="flex-1 truncate text-sm">{item.originalName}</span>
              <button
                type="button"
                onClick={() =>
                  onChange({
                    attachments: draft.attachments.filter((entry) => entry.id !== item.id),
                  })
                }
                className="text-sm font-medium text-danger underline"
              >
                {t("wizard.step4.remove")}
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </StepShell>
  );
}

interface ContactPayload {
  demarche: "CONSEIL" | "SIGNALEMENT" | "RECLAMATION";
  anonymous: boolean;
  telephone?: string;
  email?: string;
  nom?: string;
  phoneVerified: boolean;
}

function ContactStep({
  onBack,
  submitting,
  error,
  onSubmit,
}: {
  onBack: () => void;
  submitting: boolean;
  error: string | null;
  onSubmit: (payload: ContactPayload) => void;
}) {
  const { t } = useLocale();
  const [demarche, setDemarche] = useState<ContactPayload["demarche"]>("RECLAMATION");
  const [telephone, setTelephone] = useState("");
  const [email, setEmail] = useState("");
  const [nom, setNom] = useState("");
  const [code, setCode] = useState("");
  const [codeSent, setCodeSent] = useState(false);
  const [verified, setVerified] = useState(false);
  const [otpError, setOtpError] = useState(false);

  async function requestCode() {
    setOtpError(false);
    await fetch("/api/otp/request", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ phone: telephone }),
    });
    setCodeSent(true);
  }

  async function verifyCode() {
    const response = await fetch("/api/otp/verify", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ phone: telephone, code }),
    });
    if (response.ok) {
      setVerified(true);
      setOtpError(false);
    } else {
      setOtpError(true);
    }
  }

  const hasContact = Boolean(telephone.trim() || email.trim());
  const needsName = demarche === "RECLAMATION";
  const canSubmit = hasContact && (!needsName || Boolean(nom.trim()));

  return (
    <StepShell
      step={6}
      total={TOTAL_STEPS}
      title={t("wizard.step6.title")}
      hint={t("wizard.step6.hint")}
      onBack={onBack}
    >
      <fieldset className="mb-5">
        <legend className="mb-2 text-sm font-semibold">{t("wizard.step6.demarche")}</legend>
        <div className="grid gap-2">
          {(["CONSEIL", "SIGNALEMENT", "RECLAMATION"] as const).map((value) => (
            <button
              key={value}
              type="button"
              aria-pressed={demarche === value}
              onClick={() => setDemarche(value)}
              className={`rounded-xl border-2 p-3 text-start ${
                demarche === value ? "border-primary bg-primary-soft" : "border-line bg-surface"
              }`}
            >
              <span className="block font-medium">
                {t(`wizard.step6.demarche${value.charAt(0)}${value.slice(1).toLowerCase()}`)}
              </span>
              <span className="block text-sm text-muted">
                {t(`wizard.step6.demarche${value.charAt(0)}${value.slice(1).toLowerCase()}Hint`)}
              </span>
            </button>
          ))}
        </div>
      </fieldset>

      <div className="space-y-4">
        <label className="block">
          <span className="text-sm font-medium">{t("wizard.step6.phone")}</span>
          <div className="mt-1 flex gap-2">
            <input
              type="tel"
              inputMode="tel"
              value={telephone}
              onChange={(event) => {
                setTelephone(event.target.value);
                setVerified(false);
                setCodeSent(false);
              }}
              placeholder={t("wizard.step6.phonePlaceholder")}
              className="min-w-0 flex-1 rounded-xl border-2 border-line bg-surface p-3"
            />
            {telephone.trim() && !verified ? (
              <button
                type="button"
                onClick={requestCode}
                className="shrink-0 rounded-xl border-2 border-primary px-3 text-sm font-semibold text-primary"
              >
                {t("wizard.step6.sendCode")}
              </button>
            ) : null}
          </div>
          {verified ? (
            <span className="mt-1 block text-sm font-medium text-success">
              ✓ {t("wizard.step6.verified")}
            </span>
          ) : null}
        </label>

        {codeSent && !verified ? (
          <div className="rounded-xl border-2 border-primary-soft bg-primary-soft p-3">
            <p className="text-sm text-muted">{t("wizard.step6.codeSent")}</p>
            <div className="mt-2 flex gap-2">
              <input
                type="text"
                inputMode="numeric"
                value={code}
                onChange={(event) => setCode(event.target.value)}
                placeholder={t("wizard.step6.codePlaceholder")}
                className="min-w-0 flex-1 rounded-lg border-2 border-line bg-surface p-2.5"
              />
              <button
                type="button"
                onClick={verifyCode}
                className="shrink-0 rounded-lg bg-primary px-4 text-sm font-semibold text-white"
              >
                {t("wizard.step6.verify")}
              </button>
            </div>
            {otpError ? <p className="mt-2 text-sm text-danger">{t("wizard.step6.codeInvalid")}</p> : null}
          </div>
        ) : null}

        <label className="block">
          <span className="text-sm font-medium">{t("wizard.step6.email")}</span>
          <input
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3"
          />
        </label>

        {needsName ? (
          <label className="block">
            <span className="text-sm font-medium">{t("wizard.step6.name")}</span>
            <input
              type="text"
              value={nom}
              onChange={(event) => setNom(event.target.value)}
              className="mt-1 w-full rounded-xl border-2 border-line bg-surface p-3"
            />
            <span className="mt-1 block text-xs text-muted">{t("wizard.step6.nameHint")}</span>
          </label>
        ) : null}
      </div>

      {error ? <p className="mt-4 text-danger">{error}</p> : null}

      <div className="mt-6 space-y-3">
        <PrimaryButton
          disabled={!canSubmit || submitting}
          onClick={() =>
            onSubmit({ demarche, anonymous: false, telephone, email, nom, phoneVerified: verified })
          }
        >
          {submitting ? t("wizard.submitting") : t("wizard.submit")}
        </PrimaryButton>

        <div className="rounded-xl border border-line bg-surface p-4">
          <button
            type="button"
            disabled={submitting}
            onClick={() =>
              onSubmit({ demarche: "SIGNALEMENT", anonymous: true, phoneVerified: false })
            }
            className="font-semibold text-primary underline"
          >
            {t("wizard.step6.anonymous")}
          </button>
          <p className="mt-1 text-sm text-muted">{t("wizard.step6.anonymousNote")}</p>
        </div>
      </div>
    </StepShell>
  );
}

async function uploadFile(
  file: File,
  draftId: string,
  kind: string,
): Promise<DraftAttachment | null> {
  const body = new FormData();
  body.append("file", file);
  body.append("draftId", draftId);
  body.append("kind", kind);
  try {
    const response = await fetch("/api/attachments", { method: "POST", body });
    if (!response.ok) return null;
    return (await response.json()) as DraftAttachment;
  } catch {
    return null;
  }
}
