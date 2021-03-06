<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <a
        v-bind:href="'/plugins/tracker/?aid=' + encodeURIComponent(test_definition.id)"
        class="tlp-card tlp-card-selectable test-plan-test-definition-card"
        v-bind:class="classname"
    >
        <div class="test-plan-test-definition-xref-title">
            <span class="test-plan-test-definition-xref">
                {{ test_definition.short_type }} #{{ test_definition.id }}
            </span>
            {{ test_definition.summary }}
        </div>
        <div class="test-plan-test-definition-icons">
            <i
                class="fa test-plan-test-definition-icon-automated-tests"
                v-bind:class="automated_icon_status"
                aria-hidden="true"
                v-if="test_definition.automated_tests"
                data-test="automated-test-icon"
            ></i>
            <i
                class="fa fa-fw"
                v-bind:class="icon_status"
                aria-hidden="true"
                v-if="test_definition.test_status"
                data-test="test-status-icon"
            ></i>
        </div>
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { BacklogItem, TestDefinition } from "../../../type";
import { RemoveIsJustRefreshedFlagOnTestDefinitionPayload } from "../../../store/backlog-item/type";
import { namespace } from "vuex-class";

const backlog_item_store = namespace("backlog_item");

@Component
export default class TestDefinitionCard extends Vue {
    @Prop({ required: true })
    readonly test_definition!: TestDefinition;

    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @backlog_item_store.Mutation
    readonly removeIsJustRefreshedFlagOnTestDefinition!: (
        payload: RemoveIsJustRefreshedFlagOnTestDefinitionPayload
    ) => void;

    mounted(): void {
        if (this.test_definition.is_just_refreshed) {
            setTimeout(() => {
                this.removeIsJustRefreshedFlagOnTestDefinition({
                    backlog_item: this.backlog_item,
                    test_definition: this.test_definition,
                });
            }, 1000);
        }
    }

    get classname(): string {
        if (this.test_definition.is_just_refreshed) {
            return "test-plan-test-definition-is-just-refreshed";
        }

        return "";
    }

    get icon_status(): string {
        switch (this.test_definition.test_status) {
            case "passed":
                return "fa-check-circle test-plan-test-definition-icon-status-passed";
            case "failed":
                return "fa-times-circle test-plan-test-definition-icon-status-failed";
            case "blocked":
                return "fa-exclamation-circle test-plan-test-definition-icon-status-blocked";
            case "notrun":
                return "fa-question-circle test-plan-test-definition-icon-status-notrun";
            default:
                return "";
        }
    }

    get automated_icon_status(): string {
        switch (this.test_definition.test_status) {
            case "passed":
                return "fa-tlp-robot-happy test-plan-test-definition-icon-status-passed";
            case "failed":
                return "fa-tlp-robot-unhappy test-plan-test-definition-icon-status-failed";
            case "blocked":
                return "fa-tlp-robot test-plan-test-definition-icon-status-blocked";
            case "notrun":
                return "fa-tlp-robot test-plan-test-definition-icon-status-notrun";
            default:
                return "fa-tlp-robot";
        }
    }
}
</script>
