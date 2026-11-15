<template>
  <div
    class="overflow-hidden rounded-md border-2 border-solid border-transparent transition-all focus-within:bg-background-hover focus-within:shadow-md hover:bg-background-hover hover:shadow-md dark:bg-background-dark"
    :class="{
      'border-primary-element': isOpenInSidebar
    }"
  >
    <TransactionListItemTemplate
      class="hidden md:grid"
      :item-class="{
        'bg-unbalanced dark:bg-unbalanced-dark': isUnbalanced,
        'animate-pulse bg-[var(--color-warning)]': isPendingDelete
      }"
    >
      <template #actionFirst>
        <div>
          <NcLoadingIcon v-if="isLoading" />
        </div>
      </template>

      <template #date>
        <DateInput
          :date="dayjs(transaction.date).toDate()"
          :placeholder="t('money', 'Date')"
          :editable="!isPendingDelete"
          @date-changed="handleDateChanged"
        />
      </template>

      <template #description>
        <div class="flex w-full items-center gap-1">
          <SeamlessInput
            class="grow"
            :placeholder="t('money', 'Description')"
            :value="transaction.description"
            :disabled="isPendingDelete"
            @value-changed="handleDescriptionChanged"
          />
          <NcIconSvgWrapper
            v-if="isPendingDelete"
            :path="mdiUndo"
            :size="20"
            :name="t('money', 'Undo')"
            @click="handleUndoDeleteTransaction"
          />
          <NcIconSvgWrapper
            v-else
            :path="mdiDelete"
            :size="20"
            :name="t('money', 'Delete transaction')"
            @click="handleDeleteTransaction"
          />
        </div>
      </template>

      <template #account>
        <span
          v-if="hasMultipleDestinationSplits"
          class="whitespace-nowrap"
        >
          ( {{ t('money', 'Multiple Accounts') }} )
        </span>
        <AccountSelect
          v-else
          :book-id="bookId"
          :account-id="destinationAccountId"
          :editable="valueIsEditable && !isPendingDelete"
          :excluded-account-ids="excludedAccountIds"
          @account-changed="handleDestinationAccountChanged"
        />
      </template>

      <template #amount>
        <CurrencyInput
          :value="value"
          :editable="valueIsEditable && !isPendingDelete"
          :placeholder="t('money', 'Value')"
          :inverted-value="invertedValue"
          @value-changed="handleValueChanged"
        />
      </template>

      <template #actionLast>
        <NcIconSvgWrapper
          v-if="isOpenInSidebar"
          :path="mdiClose"
          @click="handleCloseSidebar"
        />
        <NcIconSvgWrapper
          v-else
          :path="mdiMenuOpen"
          @click="handleOpenSidebar"
        />
      </template>
    </TransactionListItemTemplate>

    <MobileTransactionListItemTemplate
      class="md:hidden"
      :item-class="{
        'bg-unbalanced dark:bg-unbalanced-dark': isUnbalanced,
        'animate-pulse bg-[var(--color-warning)]': isPendingDelete
      }"
      @click="handleToggleSidebar"
    >
      <template #content>
        <div
          class="flex flex-col items-start overflow-hidden whitespace-nowrap"
        >
          <div class="w-full overflow-hidden text-ellipsis">
            {{ transaction.description }}
          </div>
          <div class="text-xs">
            {{ dayjs(transaction.date).format('L') }}
          </div>
        </div>
      </template>

      <template #amount>
        <div class="flex items-center gap-1">
          <CurrencyText
            :value="value"
            :inverted-value="invertedValue"
          />
          <NcIconSvgWrapper
            v-if="isPendingDelete"
            :path="mdiUndo"
            :size="18"
            :name="t('money', 'Undo')"
            @click.stop="handleUndoDeleteTransaction"
          />
          <NcIconSvgWrapper
            v-else
            :path="mdiDelete"
            :size="18"
            :name="t('money', 'Delete transaction')"
            @click.stop="handleDeleteTransaction"
          />
        </div>
      </template>
    </MobileTransactionListItemTemplate>
  </div>
</template>

<script setup lang="ts">
  import dayjs from 'dayjs';

  import {
    ref,
    type PropType,
    type Ref,
    watch,
    computed,
    onMounted,
    onUnmounted
  } from 'vue';
  import { useRouter, useRoute } from 'vue-router';

  import { translate as t } from '@nextcloud/l10n';

  import { mdiMenuOpen, mdiClose, mdiDelete, mdiUndo } from '@mdi/js';

  import { NcIconSvgWrapper, NcLoadingIcon } from '@nextcloud/vue';

  import { NumberUtils } from '../utils/numberUtils';
  import { DateUtils } from '../utils/DateUtils';

  import type { Transaction } from '../stores/transactionStore';
  import { useTransactionService } from '../services/transactionService';
  import { useSplitStore, type Split } from '../stores/splitStore';
  import { useSplitService } from '../services/splitService';

  import TransactionListItemTemplate from './TransactionListItemTemplate.vue';
  import MobileTransactionListItemTemplate from './MobileTransactionListItemTemplate.vue';
  import AccountSelect from './AccountSelect.vue';
  import CurrencyInput from './CurrencyInput.vue';
  import SeamlessInput from './SeamlessInput.vue';
  import DateInput from './DateInput.vue';
  import CurrencyText from './CurrencyText.vue';

  const router = useRouter();
  const route = useRoute();

  const transactionService = useTransactionService();
  const splitStore = useSplitStore();
  const splitService = useSplitService();

  const props = defineProps({
    bookId: {
      type: Number,
      required: true
    },
    transaction: {
      type: Object as PropType<Transaction>,
      required: true
    },
    accountId: {
      type: Number,
      default: undefined
    },
    invertedValue: {
      type: Boolean,
      default: false
    }
  });

  const splits = ref<Array<Split>>([]);
  const transactionIdWatcher = ref<{ stop: () => void } | null>(null);
  const isLoading = ref(false);
  const deleteTransactionTimeout: Ref<null | number> = ref(null);

  watch(
    () => props.transaction,
    async () => {
      // The row may get recycled for another transaction while a delete is
      // pending (virtual scrolling), so cancel it.
      handleUndoDeleteTransaction();

      removeTransactionIdWatcher();
      await setTransactionIdWatcher();
    }
  );

  const unbalancedValue = computed(() => {
    return splits.value.reduce((value, s) => (value += s.value), 0.0);
  });

  const isUnbalanced = computed(() => {
    return NumberUtils.areNotEqual(unbalancedValue.value, 0.0);
  });

  const isPendingDelete = computed(() => {
    return deleteTransactionTimeout.value != null;
  });

  const value = computed(() => {
    if (props.accountId === undefined) return unbalancedValue.value;

    return splitsOfAccount.value.reduce((v, split) => {
      return (v += split.value);
    }, 0.0);
  });

  const splitOfAccount = computed(() => {
    return splitsOfAccount.value.length > 1
      ? undefined
      : splitsOfAccount.value[0];
  });

  const splitsOfAccount = computed(() => {
    return splits.value.filter((s) => s.destAccountId === props.accountId);
  });

  const splitOfDestinationAccount = computed(() => {
    return hasMultipleDestinationSplits.value
      ? undefined
      : splitsOfDestinationAccounts.value[0];
  });

  const splitsOfDestinationAccounts = computed(() => {
    return splits.value.filter(
      (s) => props.accountId && s.destAccountId !== props.accountId
    );
  });

  const hasMultipleDestinationSplits = computed(() => {
    return splitsOfDestinationAccounts.value.length > 1;
  });

  const destinationAccountId = computed(() => {
    return splitOfDestinationAccount.value?.destAccountId;
  });

  const valueIsEditable = computed(() => {
    return props.accountId !== undefined && !hasMultipleDestinationSplits.value;
  });

  const excludedAccountIds = computed((): Array<number> => {
    if (props.accountId) {
      return [props.accountId];
    } else {
      return [];
    }
  });

  const isOpenInSidebar = computed(() => {
    return (
      route.params.transactionId?.toString() === props.transaction.id.toString()
    );
  });

  async function handleOpenSidebar(): Promise<void> {
    await router.push({
      name:
        route.name === 'unbalanced'
          ? 'unbalanced-transaction-details'
          : 'transaction-details',
      params: {
        transactionId: props.transaction.id.toString()
      }
    });
  }

  async function handleCloseSidebar(): Promise<void> {
    if (route.name === 'unbalanced-transaction-details') {
      await router.push({
        name: 'unbalanced',
        params: {
          bookId: props.bookId
        }
      });
    } else {
      await router.push({
        name: 'account'
      });
    }
  }

  async function handleToggleSidebar(): Promise<void> {
    if (isOpenInSidebar.value) {
      await handleCloseSidebar();
    } else {
      await handleOpenSidebar();
    }
  }

  async function handleTransactionChanged(): Promise<void> {
    isLoading.value = true;
    await transactionService.updateTransaction(props.transaction);
    isLoading.value = false;
  }

  async function handleDateChanged(date: Date): Promise<void> {
    props.transaction.date = DateUtils.getDateStringForTransaction(date);
    await handleTransactionChanged();
  }

  async function handleDescriptionChanged(description: string): Promise<void> {
    props.transaction.description = description;
    await handleTransactionChanged();
  }

  async function handleValueChanged(value: number): Promise<void> {
    if (hasMultipleDestinationSplits.value)
      throw new Error('cannot change value of multi-split-transaction');

    const split = splitOfDestinationAccount.value;
    if (split) {
      split.value = -value;
      await handleSplitChanged(split);
    } else {
      // No split on any destination account exists yet, so there is no
      // counter-split to negate the new value against. The change still lands
      // via the split of the current account below; balancing the transaction
      // continues in the sidebar.
    }

    const splitOfAcc = splitOfAccount.value;
    if (splitOfAcc) {
      splitOfAcc.value = value;
      await handleSplitChanged(splitOfAcc);
    } else if (props.accountId) {
      await splitService.addSplit({
        transactionId: props.transaction.id,
        destAccountId: props.accountId,
        value: value,
        convertRate: 1.0,
        description: ''
      });
    }
  }

  function handleDeleteTransaction(): void {
    if (deleteTransactionTimeout.value != null) return;

    const transactionId = props.transaction.id;

    deleteTransactionTimeout.value = window.setTimeout(() => {
      deleteTransactionTimeout.value = null;

      if (props.transaction.id !== transactionId) return;

      void handleConfirmedDeleteTransaction();
    }, 10000);
  }

  function handleUndoDeleteTransaction(): void {
    if (deleteTransactionTimeout.value != null) {
      window.clearTimeout(deleteTransactionTimeout.value);
      deleteTransactionTimeout.value = null;
    }
  }

  async function handleConfirmedDeleteTransaction(): Promise<void> {
    if (isOpenInSidebar.value) await handleCloseSidebar();

    await transactionService.deleteTransaction(props.transaction);
  }

  async function handleDestinationAccountChanged(
    accountId?: number
  ): Promise<void> {
    if (hasMultipleDestinationSplits.value)
      throw new Error(
        'cannot change destination account of multi-split-transaction'
      );

    const split = splitOfDestinationAccount.value;
    if (!split) {
      if (accountId) {
        await splitService.addSplit({
          transactionId: props.transaction.id,
          destAccountId: accountId,
          value: -value.value,
          convertRate: 1.0,
          description: ''
        });
      }
    } else if (accountId) {
      split.destAccountId = accountId;
      await handleSplitChanged(split);
    } else {
      await handleSplitDeleted(split);
    }
  }

  async function handleSplitDeleted(split: Split): Promise<void> {
    await splitService.deleteSplit(split);
  }

  async function handleSplitChanged(split: Split): Promise<void> {
    isLoading.value = true;
    await splitService.updateSplit(split);
    isLoading.value = false;
  }

  function removeTransactionIdWatcher(): void {
    transactionIdWatcher.value?.stop();
    transactionIdWatcher.value = null;
  }

  async function setTransactionIdWatcher(): Promise<void> {
    const transactionId = props.transaction.id;

    const watcher = await splitStore.watchForTransactionId(
      transactionId,
      (s) => {
        splits.value = s.slice();
      }
    );

    if (transactionId !== props.transaction.id) {
      watcher.stop();
    } else {
      transactionIdWatcher.value = watcher;
    }
  }

  onMounted(() => {
    void setTransactionIdWatcher();
  });

  onUnmounted(() => {
    removeTransactionIdWatcher();
  });
</script>
