-- CreateTable
CREATE TABLE `Dossier` (
    `id` VARCHAR(191) NOT NULL,
    `reference` VARCHAR(30) NOT NULL,
    `demarche` ENUM('CONSEIL', 'SIGNALEMENT', 'RECLAMATION') NOT NULL,
    `categorie` VARCHAR(60) NOT NULL,
    `motif` VARCHAR(60) NOT NULL,
    `description` TEXT NULL,
    `resultatAttendu` VARCHAR(60) NULL,
    `status` ENUM('RECU', 'A_VERIFIER', 'INFOS_DEMANDEES', 'ORIENTE_ASSOCIATION', 'TRANSMIS_PROFESSIONNEL', 'EN_MEDIATION', 'TRANSMIS_AUTORITE', 'RESOLU', 'CLOTURE', 'SIGNALEMENT_COLLECTIF') NOT NULL DEFAULT 'RECU',
    `region` VARCHAR(60) NULL,
    `etablissement` VARCHAR(255) NULL,
    `professionnel` VARCHAR(255) NULL,
    `trackingTokenHash` VARCHAR(80) NOT NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,
    `firstHandledAt` DATETIME(3) NULL,
    `assignedAssociationId` VARCHAR(191) NULL,
    `qrCodeId` VARCHAR(191) NULL,

    UNIQUE INDEX `Dossier_reference_key`(`reference`),
    INDEX `Dossier_status_idx`(`status`),
    INDEX `Dossier_categorie_idx`(`categorie`),
    INDEX `Dossier_assignedAssociationId_idx`(`assignedAssociationId`),
    INDEX `Dossier_createdAt_idx`(`createdAt`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `Requerant` (
    `id` VARCHAR(191) NOT NULL,
    `dossierId` VARCHAR(191) NOT NULL,
    `telephone` VARCHAR(30) NULL,
    `whatsapp` VARCHAR(30) NULL,
    `email` VARCHAR(255) NULL,
    `nom` VARCHAR(150) NULL,
    `adresse` TEXT NULL,
    `phoneVerifiedAt` DATETIME(3) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `Requerant_dossierId_key`(`dossierId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `Attachment` (
    `id` VARCHAR(191) NOT NULL,
    `dossierId` VARCHAR(191) NULL,
    `draftId` VARCHAR(191) NULL,
    `kind` ENUM('TICKET', 'FACTURE', 'PRODUIT', 'CAPTURE', 'AUDIO', 'AUTRE') NOT NULL,
    `storageKey` VARCHAR(120) NOT NULL,
    `originalName` VARCHAR(255) NOT NULL,
    `mimeType` VARCHAR(120) NOT NULL,
    `size` INTEGER NOT NULL,
    `scanStatus` ENUM('SKIPPED', 'PENDING', 'CLEAN', 'INFECTED') NOT NULL DEFAULT 'SKIPPED',
    `transcription` TEXT NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `Attachment_draftId_idx`(`draftId`),
    INDEX `Attachment_dossierId_idx`(`dossierId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `DossierEvent` (
    `id` VARCHAR(191) NOT NULL,
    `dossierId` VARCHAR(191) NOT NULL,
    `fromStatus` ENUM('RECU', 'A_VERIFIER', 'INFOS_DEMANDEES', 'ORIENTE_ASSOCIATION', 'TRANSMIS_PROFESSIONNEL', 'EN_MEDIATION', 'TRANSMIS_AUTORITE', 'RESOLU', 'CLOTURE', 'SIGNALEMENT_COLLECTIF') NULL,
    `toStatus` ENUM('RECU', 'A_VERIFIER', 'INFOS_DEMANDEES', 'ORIENTE_ASSOCIATION', 'TRANSMIS_PROFESSIONNEL', 'EN_MEDIATION', 'TRANSMIS_AUTORITE', 'RESOLU', 'CLOTURE', 'SIGNALEMENT_COLLECTIF') NULL,
    `note` TEXT NULL,
    `publicNote` BOOLEAN NOT NULL DEFAULT true,
    `actorId` VARCHAR(191) NULL,
    `actorLabel` VARCHAR(150) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `DossierEvent_dossierId_idx`(`dossierId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `Association` (
    `id` VARCHAR(191) NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    `contact` VARCHAR(255) NULL,
    `active` BOOLEAN NOT NULL DEFAULT true,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `AssociationScope` (
    `id` VARCHAR(191) NOT NULL,
    `associationId` VARCHAR(191) NOT NULL,
    `kind` ENUM('REGION', 'SECTEUR') NOT NULL,
    `value` VARCHAR(100) NOT NULL,

    INDEX `AssociationScope_kind_value_idx`(`kind`, `value`),
    UNIQUE INDEX `AssociationScope_associationId_kind_value_key`(`associationId`, `kind`, `value`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `RoutingRule` (
    `id` VARCHAR(191) NOT NULL,
    `categorie` VARCHAR(60) NOT NULL,
    `region` VARCHAR(60) NULL,
    `priority` INTEGER NOT NULL DEFAULT 100,
    `associationId` VARCHAR(191) NOT NULL,

    INDEX `RoutingRule_categorie_region_idx`(`categorie`, `region`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `AdminUser` (
    `id` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `passwordHash` VARCHAR(100) NOT NULL,
    `nom` VARCHAR(150) NOT NULL,
    `role` ENUM('FMDC_ADMIN', 'ASSOCIATION_AGENT') NOT NULL DEFAULT 'ASSOCIATION_AGENT',
    `associationId` VARCHAR(191) NULL,
    `active` BOOLEAN NOT NULL DEFAULT true,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `AdminUser_email_key`(`email`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `QrCode` (
    `id` VARCHAR(191) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `type` ENUM('NATIONAL', 'SECTORIEL', 'ETABLISSEMENT') NOT NULL,
    `signature` VARCHAR(80) NOT NULL,
    `libelle` VARCHAR(255) NOT NULL,
    `secteur` VARCHAR(60) NULL,
    `region` VARCHAR(60) NULL,
    `etablissement` VARCHAR(255) NULL,
    `support` VARCHAR(255) NULL,
    `active` BOOLEAN NOT NULL DEFAULT true,
    `scanCount` INTEGER NOT NULL DEFAULT 0,
    `reportedCount` INTEGER NOT NULL DEFAULT 0,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `QrCode_code_key`(`code`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `OtpChallenge` (
    `id` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `codeHash` VARCHAR(80) NOT NULL,
    `expiresAt` DATETIME(3) NOT NULL,
    `attempts` INTEGER NOT NULL DEFAULT 0,
    `consumedAt` DATETIME(3) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `OtpChallenge_phone_idx`(`phone`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `AuditLog` (
    `id` VARCHAR(191) NOT NULL,
    `actorId` VARCHAR(191) NULL,
    `actorLabel` VARCHAR(150) NULL,
    `action` VARCHAR(80) NOT NULL,
    `entityType` VARCHAR(60) NOT NULL,
    `entityId` VARCHAR(60) NOT NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `AuditLog_entityType_entityId_idx`(`entityType`, `entityId`),
    INDEX `AuditLog_createdAt_idx`(`createdAt`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `Dossier` ADD CONSTRAINT `Dossier_assignedAssociationId_fkey` FOREIGN KEY (`assignedAssociationId`) REFERENCES `Association`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `Dossier` ADD CONSTRAINT `Dossier_qrCodeId_fkey` FOREIGN KEY (`qrCodeId`) REFERENCES `QrCode`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `Requerant` ADD CONSTRAINT `Requerant_dossierId_fkey` FOREIGN KEY (`dossierId`) REFERENCES `Dossier`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `Attachment` ADD CONSTRAINT `Attachment_dossierId_fkey` FOREIGN KEY (`dossierId`) REFERENCES `Dossier`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `DossierEvent` ADD CONSTRAINT `DossierEvent_dossierId_fkey` FOREIGN KEY (`dossierId`) REFERENCES `Dossier`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `AssociationScope` ADD CONSTRAINT `AssociationScope_associationId_fkey` FOREIGN KEY (`associationId`) REFERENCES `Association`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `RoutingRule` ADD CONSTRAINT `RoutingRule_associationId_fkey` FOREIGN KEY (`associationId`) REFERENCES `Association`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `AdminUser` ADD CONSTRAINT `AdminUser_associationId_fkey` FOREIGN KEY (`associationId`) REFERENCES `Association`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
