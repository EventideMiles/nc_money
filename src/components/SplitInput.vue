<template>
  <div
    class="grid grid-cols-2"
    :class="{
      'opacity-50': isPendingDelete
    }"
  >
    <AccountSelect
      :book-id="bookId"
      :account-id="split.destAccountId"
      :editable="editable && !isPendingDelete"
      :excluded-account-ids="
        excludedAccountIds.filter(
          (accountId) => accountId !== split.destAccountId
        )
      "
      @account-changed="handleSplitAccountChanged(split, $event)"
    />
    <CurrencyInput
      :value="split.value"
      :editable="editable && !isPendingDelete"
      :placeholder="t('money', 'Value')"
      :inverted-value="invertedValue"
      @value-changed="handleSplitValueChanged(split, $event)"
    />
    <div class="col-span-2 flex items-center gap-1">
      <SeamlessInput
        class="grow"
        :value="split.description"
        :placeholder="t('money', 'Description')"
        :disabled="!editable || isPendingDelete"
        @value-changed="handleSplitDescriptionChanged(split, $event)"
      />
      <NcIconSvgWrapper
        v-if="split.id"
        :path="isPendingDelete ? mdiUndo : mdiDelete"
        :size="20"
        :name="
          isPendingDelete ? t('money', 'Undo') : t('money', 'Delete split')
        "
        @click="
          isPendingDelete ? handleUndoSplitDelete() : handleSplitDeleteClicked()
        "
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref, type PropType, type Ref } from 'vue';

  import { translate as t } from '@nextcloud/l10n';

  import { showSuccess } from '@nextcloud/dialogs';

  import { mdiDelete, mdiUndo } from '@mdi/js';

  import { NcIconSvgWrapper } from '@nextcloud/vue';

  import AccountSelect from './AccountSelect.vue';
  import CurrencyInput from './CurrencyInput.vue';
  import SeamlessInput from './SeamlessInput.vue';

  import type { Split } from '../stores/splitStore';
  import { useSplitStore } from '../stores/splitStore';
  import { useTransactionStore } from '../stores/transactionStore';
  import { useSplitService } from '../services/splitService';
  import { useTransactionService } from '../services/transactionService';

  const splitService = useSplitService();
  const transactionService = useTransactionService();

  const splitStore = useSplitStore();
  const transactionStore = useTransactionStore();

  const props = defineProps({
    bookId: {
      type: Number,
      required: true
    },
    split: {
      type: Object as PropType<Split>,
      required: true
    },
    excludedAccountIds: {
      type: Array as PropType<Array<number>>,
      default: () => []
    },
    invertedValue: {
      type: Boolean,
      default: false
    },
    editable: {
      type: Boolean,
      default: true
    }
  });

  const emit = defineEmits<{
    (event: 'transaction-deleted', transactionId: number): void;
  }>();

  const pendingDeleteTimeout: Ref<null | number> = ref(null);

  const isPendingDelete = computed(() => {
    return pendingDeleteTimeout.value != null;
  });

  async function handleSplitAccountChanged(
    split: Split,
    accountId?: number
  ): Promise<void> {
    if (accountId) {
      split.destAccountId = accountId;
      await handleSplitChanged(split);
    } else {
      await splitService.deleteSplit(split);
    }
  }

  async function handleSplitValueChanged(
    split: Split,
    value: number
  ): Promise<void> {
    split.value = value;
    await handleSplitChanged(split);
  }

  async function handleSplitDescriptionChanged(
    split: Split,
    description: string
  ): Promise<void> {
    split.description = description;
    await handleSplitChanged(split);
  }

  async function handleSplitChanged(split: Split): Promise<void> {
    await splitService.updateSplit(split);
  }

  function handleSplitDeleteClicked(): void {
    if (pendingDeleteTimeout.value != null) return;

    pendingDeleteTimeout.value = window.setTimeout(() => {
      pendingDeleteTimeout.value = null;
      void handleConfirmedSplitDelete();
    }, 10000);
  }

  function handleUndoSplitDelete(): void {
    if (pendingDeleteTimeout.value != null) {
      window.clearTimeout(pendingDeleteTimeout.value);
      pendingDeleteTimeout.value = null;
    }
  }

  async function handleConfirmedSplitDelete(): Promise<void> {
    const transactionId = props.split.transactionId;

    const splitsOfTransaction =
      await splitStore.getByTransactionId(transactionId);

    await splitService.deleteSplit(props.split);

    if (splitsOfTransaction.length > 1) return;

    // The transaction has no splits left now and would only linger until the
    // cleanup job runs, so delete it as well.
    const transaction = await transactionStore.getById(transactionId);
    if (!transaction) return;

    await transactionService.deleteTransaction(transaction);

    showSuccess(
      t('money', "'{transactionName}' deleted", {
        transactionName: transaction.description
      })
    );

    emit('transaction-deleted', transactionId);
  }
</script>
