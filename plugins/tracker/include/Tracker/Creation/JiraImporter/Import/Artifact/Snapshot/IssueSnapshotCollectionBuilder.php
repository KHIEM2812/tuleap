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
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class IssueSnapshotCollectionBuilder
{
    /**
     * @var InitialSnapshotBuilder
     */
    private $initial_snapshot_builder;

    /**
     * @var ChangelogEntriesBuilder
     */
    private $changelog_entries_builder;

    /**
     * @var ChangelogSnapshotBuilder
     */
    private $changelog_snapshot_builder;

    /**
     * @var CurrentSnapshotBuilder
     */
    private $current_snapshot_builder;

    /**
     * @var CommentValuesBuilder
     */
    private $comment_values_builder;

    public function __construct(
        ChangelogEntriesBuilder $changelog_entries_builder,
        CurrentSnapshotBuilder $current_snapshot_builder,
        InitialSnapshotBuilder $initial_snapshot_builder,
        ChangelogSnapshotBuilder $changelog_snapshot_builder,
        CommentValuesBuilder $comment_values_builder
    ) {
        $this->initial_snapshot_builder   = $initial_snapshot_builder;
        $this->changelog_entries_builder  = $changelog_entries_builder;
        $this->changelog_snapshot_builder = $changelog_snapshot_builder;
        $this->current_snapshot_builder   = $current_snapshot_builder;
        $this->comment_values_builder     = $comment_values_builder;
    }

    /**
     * @return Snapshot[]
     */
    public function buildCollectionOfSnapshotsForIssue(
        PFUser $forge_user,
        array $jira_issue_api,
        FieldMappingCollection $jira_field_mapping_collection,
        string $jira_base_url
    ): array {
        $logger = new \BackendLogger('/tmp/jira.log');
        $logger->info(__METHOD__);

        $jira_issue_key = $jira_issue_api['key'];

        $snapshots_collection = [];
        $changelog_entries    = $this->changelog_entries_builder->buildEntriesCollectionForIssue($jira_issue_key);

        $current_snapshot = $this->current_snapshot_builder->buildCurrentSnapshot(
            $forge_user,
            $jira_issue_api,
            $jira_field_mapping_collection
        );

        $initial_snapshot = $this->initial_snapshot_builder->buildInitialSnapshot(
            $forge_user,
            $current_snapshot,
            $changelog_entries,
            $jira_field_mapping_collection,
            $jira_issue_api,
            $jira_base_url
        );

        $snapshots_collection[$initial_snapshot->getDate()->getTimestamp()] = $initial_snapshot;

        foreach ($changelog_entries as $changelog_entry) {
            $changelog_snapshot = $this->changelog_snapshot_builder->buildSnapshotFromChangelogEntry(
                $forge_user,
                $current_snapshot,
                $changelog_entry,
                $jira_field_mapping_collection
            );

            if (count($changelog_snapshot->getAllFieldsSnapshot()) > 0) {
                $snapshots_collection[$changelog_snapshot->getDate()->getTimestamp()] = $changelog_snapshot;
            }
        }

        $comments_collection = $this->comment_values_builder->buildCommentCollectionForIssue($jira_issue_key);
        foreach ($comments_collection as $comment) {
            $comment_snapshot = new Snapshot(
                $forge_user,
                $comment->getDate(),
                [],
                $comment
            );

            if ($comment_snapshot->getCommentSnapshot() !== null) {
                $snapshots_collection[$comment_snapshot->getDate()->getTimestamp()] = $comment_snapshot;
            }
        }

        ksort($snapshots_collection);

        return $snapshots_collection;
    }
}
