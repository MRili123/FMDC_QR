-- CreateEnum
CREATE TYPE "DossierStatus" AS ENUM ('RECU', 'A_VERIFIER', 'INFOS_DEMANDEES', 'ORIENTE_ASSOCIATION', 'TRANSMIS_PROFESSIONNEL', 'EN_MEDIATION', 'TRANSMIS_AUTORITE', 'RESOLU', 'CLOTURE', 'SIGNALEMENT_COLLECTIF');

-- CreateEnum
CREATE TYPE "Demarche" AS ENUM ('CONSEIL', 'SIGNALEMENT', 'RECLAMATION');

-- CreateEnum
CREATE TYPE "AttachmentKind" AS ENUM ('TICKET', 'FACTURE', 'PRODUIT', 'CAPTURE', 'AUDIO', 'AUTRE');

-- CreateEnum
CREATE TYPE "ScanStatus" AS ENUM ('SKIPPED', 'PENDING', 'CLEAN', 'INFECTED');

-- CreateEnum
CREATE TYPE "QrType" AS ENUM ('NATIONAL', 'SECTORIEL', 'ETABLISSEMENT');

-- CreateEnum
CREATE TYPE "AdminRole" AS ENUM ('FMDC_ADMIN', 'ASSOCIATION_AGENT');

-- CreateTable
CREATE TABLE "Dossier" (
    "id" TEXT NOT NULL,
    "reference" TEXT NOT NULL,
    "demarche" "Demarche" NOT NULL,
    "categorie" TEXT NOT NULL,
    "motif" TEXT NOT NULL,
    "description" TEXT,
    "resultatAttendu" TEXT,
    "status" "DossierStatus" NOT NULL DEFAULT 'RECU',
    "region" TEXT,
    "etablissement" TEXT,
    "professionnel" TEXT,
    "trackingTokenHash" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,
    "firstHandledAt" TIMESTAMP(3),
    "assignedAssociationId" TEXT,
    "qrCodeId" TEXT,

    CONSTRAINT "Dossier_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Requerant" (
    "id" TEXT NOT NULL,
    "dossierId" TEXT NOT NULL,
    "telephone" TEXT,
    "whatsapp" TEXT,
    "email" TEXT,
    "nom" TEXT,
    "adresse" TEXT,
    "phoneVerifiedAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "Requerant_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Attachment" (
    "id" TEXT NOT NULL,
    "dossierId" TEXT,
    "draftId" TEXT,
    "kind" "AttachmentKind" NOT NULL,
    "storageKey" TEXT NOT NULL,
    "originalName" TEXT NOT NULL,
    "mimeType" TEXT NOT NULL,
    "size" INTEGER NOT NULL,
    "scanStatus" "ScanStatus" NOT NULL DEFAULT 'SKIPPED',
    "transcription" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "Attachment_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "DossierEvent" (
    "id" TEXT NOT NULL,
    "dossierId" TEXT NOT NULL,
    "fromStatus" "DossierStatus",
    "toStatus" "DossierStatus",
    "note" TEXT,
    "publicNote" BOOLEAN NOT NULL DEFAULT true,
    "actorId" TEXT,
    "actorLabel" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "DossierEvent_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Association" (
    "id" TEXT NOT NULL,
    "nom" TEXT NOT NULL,
    "regions" TEXT[],
    "secteurs" TEXT[],
    "contact" TEXT,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "Association_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "RoutingRule" (
    "id" TEXT NOT NULL,
    "categorie" TEXT NOT NULL,
    "region" TEXT,
    "priority" INTEGER NOT NULL DEFAULT 100,
    "associationId" TEXT NOT NULL,

    CONSTRAINT "RoutingRule_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "AdminUser" (
    "id" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "passwordHash" TEXT NOT NULL,
    "nom" TEXT NOT NULL,
    "role" "AdminRole" NOT NULL DEFAULT 'ASSOCIATION_AGENT',
    "associationId" TEXT,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "AdminUser_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "QrCode" (
    "id" TEXT NOT NULL,
    "code" TEXT NOT NULL,
    "type" "QrType" NOT NULL,
    "signature" TEXT NOT NULL,
    "libelle" TEXT NOT NULL,
    "secteur" TEXT,
    "region" TEXT,
    "etablissement" TEXT,
    "support" TEXT,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "scanCount" INTEGER NOT NULL DEFAULT 0,
    "reportedCount" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "QrCode_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "OtpChallenge" (
    "id" TEXT NOT NULL,
    "phone" TEXT NOT NULL,
    "codeHash" TEXT NOT NULL,
    "expiresAt" TIMESTAMP(3) NOT NULL,
    "attempts" INTEGER NOT NULL DEFAULT 0,
    "consumedAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "OtpChallenge_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "AuditLog" (
    "id" TEXT NOT NULL,
    "actorId" TEXT,
    "actorLabel" TEXT,
    "action" TEXT NOT NULL,
    "entityType" TEXT NOT NULL,
    "entityId" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "AuditLog_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "Dossier_reference_key" ON "Dossier"("reference");

-- CreateIndex
CREATE INDEX "Dossier_status_idx" ON "Dossier"("status");

-- CreateIndex
CREATE INDEX "Dossier_categorie_idx" ON "Dossier"("categorie");

-- CreateIndex
CREATE INDEX "Dossier_assignedAssociationId_idx" ON "Dossier"("assignedAssociationId");

-- CreateIndex
CREATE INDEX "Dossier_createdAt_idx" ON "Dossier"("createdAt");

-- CreateIndex
CREATE UNIQUE INDEX "Requerant_dossierId_key" ON "Requerant"("dossierId");

-- CreateIndex
CREATE INDEX "Attachment_draftId_idx" ON "Attachment"("draftId");

-- CreateIndex
CREATE INDEX "Attachment_dossierId_idx" ON "Attachment"("dossierId");

-- CreateIndex
CREATE INDEX "DossierEvent_dossierId_idx" ON "DossierEvent"("dossierId");

-- CreateIndex
CREATE INDEX "RoutingRule_categorie_region_idx" ON "RoutingRule"("categorie", "region");

-- CreateIndex
CREATE UNIQUE INDEX "AdminUser_email_key" ON "AdminUser"("email");

-- CreateIndex
CREATE UNIQUE INDEX "QrCode_code_key" ON "QrCode"("code");

-- CreateIndex
CREATE INDEX "OtpChallenge_phone_idx" ON "OtpChallenge"("phone");

-- CreateIndex
CREATE INDEX "AuditLog_entityType_entityId_idx" ON "AuditLog"("entityType", "entityId");

-- CreateIndex
CREATE INDEX "AuditLog_createdAt_idx" ON "AuditLog"("createdAt");

-- AddForeignKey
ALTER TABLE "Dossier" ADD CONSTRAINT "Dossier_assignedAssociationId_fkey" FOREIGN KEY ("assignedAssociationId") REFERENCES "Association"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Dossier" ADD CONSTRAINT "Dossier_qrCodeId_fkey" FOREIGN KEY ("qrCodeId") REFERENCES "QrCode"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Requerant" ADD CONSTRAINT "Requerant_dossierId_fkey" FOREIGN KEY ("dossierId") REFERENCES "Dossier"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Attachment" ADD CONSTRAINT "Attachment_dossierId_fkey" FOREIGN KEY ("dossierId") REFERENCES "Dossier"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DossierEvent" ADD CONSTRAINT "DossierEvent_dossierId_fkey" FOREIGN KEY ("dossierId") REFERENCES "Dossier"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "RoutingRule" ADD CONSTRAINT "RoutingRule_associationId_fkey" FOREIGN KEY ("associationId") REFERENCES "Association"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AdminUser" ADD CONSTRAINT "AdminUser_associationId_fkey" FOREIGN KEY ("associationId") REFERENCES "Association"("id") ON DELETE SET NULL ON UPDATE CASCADE;
