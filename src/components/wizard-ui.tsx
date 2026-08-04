"use client";

import type { ReactNode } from "react";
import { useLocale } from "@/i18n/client";

export function StepShell({
  step,
  total,
  title,
  hint,
  children,
  onBack,
  footer,
}: {
  step: number;
  total: number;
  title: string;
  hint?: string;
  children: ReactNode;
  onBack?: () => void;
  footer?: ReactNode;
}) {
  const { t } = useLocale();
  const percent = Math.round((step / total) * 100);

  return (
    <div className="mx-auto max-w-2xl px-4 py-6">
      <div className="mb-5">
        <div
          className="h-1.5 overflow-hidden rounded-full bg-line"
          role="progressbar"
          aria-valuenow={step}
          aria-valuemin={1}
          aria-valuemax={total}
          aria-label={t("wizard.step", { current: step, total })}
        >
          <div className="h-full bg-primary transition-all" style={{ width: `${percent}%` }} />
        </div>
        <div className="mt-2 flex items-center justify-between text-sm text-muted">
          <span>{t("wizard.step", { current: step, total })}</span>
          {onBack ? (
            <button type="button" onClick={onBack} className="font-medium text-primary underline">
              {t("wizard.back")}
            </button>
          ) : null}
        </div>
      </div>

      <h1 className="text-xl font-bold sm:text-2xl">{title}</h1>
      {hint ? <p className="mt-1.5 text-muted">{hint}</p> : null}

      <div className="mt-5">{children}</div>
      {footer ? <div className="mt-6">{footer}</div> : null}
    </div>
  );
}

export function ChoiceCard({
  icon,
  label,
  selected,
  onClick,
}: {
  icon?: string;
  label: string;
  selected?: boolean;
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-pressed={selected}
      className={`flex w-full items-center gap-3 rounded-xl border-2 p-4 text-start transition ${
        selected
          ? "border-primary bg-primary-soft"
          : "border-line bg-surface hover:border-primary hover:bg-primary-soft"
      }`}
    >
      {icon ? (
        <span aria-hidden className="text-2xl leading-none">
          {icon}
        </span>
      ) : null}
      <span className="font-medium">{label}</span>
    </button>
  );
}

export function PrimaryButton({
  children,
  disabled,
  onClick,
  type = "button",
}: {
  children: ReactNode;
  disabled?: boolean;
  onClick?: () => void;
  type?: "button" | "submit";
}) {
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className="w-full rounded-xl bg-primary px-6 py-4 text-lg font-semibold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
    >
      {children}
    </button>
  );
}
