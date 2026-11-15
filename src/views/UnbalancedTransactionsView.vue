<template>
  <NcAppContent>
    <div class="mx-auto flex h-[calc(100vh-50px)] flex-col lg:container">
      <div
        class="mb-5 mt-8 border-b border-solid border-border-dark pb-3 text-center text-xl text-border-dark"
      >
        {{ t('money', 'Unbalanced transactions') }}
      </div>

      <NcLoadingIcon
        v-if="isLoadingTransactions && !transactions.length"
        class="my-8"
        :size="40"
      />

      <NcEmptyContent
        v-else-if="!transactions.length"
        :name="t('money', 'No unbalanced transactions')"
        :description="
          t(
            'money',
            'All transactions of this book are balanced. Nice and tidy.'
          )
        "
      >
        <template #icon>
          <NcIconSvgWrapper :path="mdiScaleBalance" />
        </template>
      </NcEmptyContent>

      <div
        v-else
        class="flex w-full flex-col overflow-scroll"
      >
        <DynamicScroller
          ref="scrollContainer"
          :items="items"
          :min-item-size="45"
          :emit-update="true"
          @update="onDynamicScrollerUpdate"
        >
          <template #default="{ item, active }">
            <DynamicScrollerItem
              :item="item"
              :active="active"
              class="p-1"
            >
              <TransactionListItem
                :book-id="bookId"
                :transaction="item.transaction"
              />
            </DynamicScrollerItem>
          </template>
        </DynamicScroller>
      </div>
    </div>
  </NcAppContent>
</template>

<script setup lang="ts">
  import { ref, computed, onMounted, onUnmounted } from 'vue';

  import { translate as t } from '@nextcloud/l10n';

  import {
    NcAppContent,
    NcEmptyContent,
    NcIconSvgWrapper,
    NcLoadingIcon
  } from '@nextcloud/vue';

  import { mdiScaleBalance } from '@mdi/js';

  import type { Transaction } from '../stores/transactionStore';
  import { useTransactionStore } from '../stores/transactionStore';
  import { useTransactionService } from '../services/transactionService';

  import TransactionListItem from '../components/TransactionListItem.vue';

  import { DynamicScroller, DynamicScrollerItem } from 'vue-virtual-scroller';

  const transactionStore = useTransactionStore();
  const transactionService = useTransactionService();

  defineProps({
    bookId: {
      type: Number,
      required: true
    }
  });

  const transactions = ref<Array<Transaction>>([]);
  const transactionWatcher = ref<{ stop: () => void } | null>(null);
  const isLoadingTransactions = ref(false);

  const items = computed<
    Array<{
      id: number;
      transaction: Transaction;
    }>
  >(() => {
    return transactions.value.map((t) => ({
      id: t.id,
      transaction: t
    }));
  });

  async function loadUnbalancedTransactions(): Promise<void> {
    isLoadingTransactions.value = true;

    // Reset the store and fetch the unbalanced transactions of the book,
    // mirroring the "no account selected" fetch path.
    await transactionService.changeAccount(null);
    transactions.value = await transactionStore.getSortedByDate();

    if (transactionWatcher.value) {
      transactionWatcher.value.stop();
    }
    transactionWatcher.value = await transactionStore.watchAll((t) => {
      transactions.value = t.slice();
    });

    isLoadingTransactions.value = false;
  }

  async function loadMoreTransactions(): Promise<void> {
    if (
      isLoadingTransactions.value ||
      transactionStore.allTransactionsFetched.value
    )
      return;

    isLoadingTransactions.value = true;

    const offset = transactions.value.length;
    await transactionService.fetchAndInsertTransactions(offset);

    isLoadingTransactions.value = false;
  }

  async function onDynamicScrollerUpdate(
    _startIndex: number,
    endIndex: number
  ): Promise<void> {
    if (endIndex + 10 >= transactions.value.length) {
      await loadMoreTransactions();
    }
  }

  onMounted(() => {
    void loadUnbalancedTransactions();
  });

  onUnmounted(() => {
    transactionWatcher.value?.stop();
  });
</script>
