<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use PFUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryItemsRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class InitialSnapshotBuilder
{
    /**
     * @var CreationStateListValueFormatter
     */
    private $creation_state_list_value_formatter;

    public function __construct(
        CreationStateListValueFormatter $creation_state_list_value_formatter
    ) {
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
    }

    /**
     * @param ChangelogEntryValueRepresentation[] $changelog_entries
     *
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    public function buildInitialSnapshot(
        PFUser $forge_user,
        Snapshot $current_snapshot,
        array $changelog_entries,
        FieldMappingCollection $jira_field_mapping_collection,
        array $jira_issue_api,
        string $jira_base_url
    ): Snapshot {
        $already_seen_fields_keys = [];
        $field_snapshots          = [];

        foreach ($changelog_entries as $changelog_entry) {
            $this->retrieveInitialFieldsValueInChangelogEntry(
                $changelog_entry,
                $current_snapshot,
                $field_snapshots,
                $already_seen_fields_keys
            );
        }

        $this->retrieveFieldsNotModifiedSinceIssueCreation(
            $current_snapshot,
            $field_snapshots,
            $already_seen_fields_keys
        );

        $this->addJiraLinkInformation(
            $field_snapshots,
            $jira_field_mapping_collection,
            $jira_issue_api,
            $jira_base_url
        );

        $initial_snapshot = new Snapshot(
            $forge_user,
            new \DateTimeImmutable($jira_issue_api['fields'][AlwaysThereFieldsExporter::JIRA_CREATED_NAME]),
            $field_snapshots,
            null
        );

        return $initial_snapshot;
    }

    private function retrieveFieldsNotModifiedSinceIssueCreation(
        Snapshot $initial_snapshot,
        array &$field_snapshots,
        array &$already_seen_fields_keys
    ): void {
        foreach ($initial_snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            $jira_field_id = $field_snapshot->getFieldMapping()->getJiraFieldId();

            if (array_key_exists($jira_field_id, $already_seen_fields_keys)) {
                continue;
            }

            $already_seen_fields_keys[$jira_field_id] = true;
            $field_snapshots[] = $field_snapshot;
        }
    }

    private function retrieveInitialFieldsValueInChangelogEntry(
        ChangelogEntryValueRepresentation $changelog_entry,
        Snapshot $current_snapshot,
        array &$field_snapshots,
        array &$already_seen_fields_keys
    ): void {
        foreach ($changelog_entry->getItemRepresentations() as $changed_field) {
            $changed_field_id       = $changed_field->getFieldId();
            $current_snapshot_field = $current_snapshot->getFieldInSnapshot($changed_field_id);
            if ($this->mustFieldBeCheckedInChangelog($current_snapshot_field, $already_seen_fields_keys)) {
                $already_seen_fields_keys[$changed_field_id] = true;

                if ($current_snapshot_field === null) {
                    continue;
                }

                $changed_field_from        = $changed_field->getFrom();
                $changed_field_from_string = $changed_field->getFromString();

                if ($this->fieldHasNoInitialValue($changed_field)) {
                    continue;
                }

                if ($this->fieldListHasInitialValue($changed_field)) {
                    $field_snapshots[] = new FieldSnapshot(
                        $current_snapshot_field->getFieldMapping(),
                        $this->creation_state_list_value_formatter->formatCreationListValue(
                            $changed_field_from
                        ),
                        $current_snapshot_field->getRenderedValue()
                    );
                    continue;
                }

                if ($this->fieldTextHasInitialValue($changed_field)) {
                    $field_snapshots[] = new FieldSnapshot(
                        $current_snapshot_field->getFieldMapping(),
                        $changed_field_from_string,
                        null
                    );
                    continue;
                }
            }
        }
    }

    private function addJiraLinkInformation(
        array &$field_snapshots,
        FieldMappingCollection $jira_field_mapping_collection,
        array $jira_issue_api,
        string $jira_base_url
    ): void {
        $jira_link_field_mapping = $jira_field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME);
        if ($jira_link_field_mapping === null) {
            return;
        }

        $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($jira_issue_api['key']);

        $field_snapshots[] = new FieldSnapshot(
            $jira_link_field_mapping,
            $jira_link,
            null
        );
    }

    private function mustFieldBeCheckedInChangelog(
        ?FieldSnapshot $current_snapshot_field,
        array $already_seen_fields_keys
    ): bool {
        return $current_snapshot_field !== null &&
            ! array_key_exists($current_snapshot_field->getFieldMapping()->getJiraFieldId(), $already_seen_fields_keys);
    }

    private function fieldHasNoInitialValue(ChangelogEntryItemsRepresentation $changed_field): bool
    {
        return $changed_field->getFrom() === null &&
            $changed_field->getFromString() === null;
    }

    private function fieldListHasInitialValue(ChangelogEntryItemsRepresentation $changed_field): bool
    {
        return $changed_field->getFrom() !== null;
    }

    private function fieldTextHasInitialValue(ChangelogEntryItemsRepresentation $changed_field): bool
    {
        return $changed_field->getFromString() !== null;
    }
}
