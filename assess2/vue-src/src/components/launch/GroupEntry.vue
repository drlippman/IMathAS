<template>
  <div>
    <div>
      {{ $t('group.isgroup') }}
    </div>
    <div v-if = "canViewAll">
      {{ teacherNote }}
    </div>
    <div v-else-if = "groupMembers.length > 0">
      {{ $t('group.members') }}
        <span v-if = "showMax">
          ({{ $t('group.max', {n: groupMax}) }})
        </span>

      <ul class="no-margin-top">
        <li v-for = "(member,index) in groupMembers" :key="index">
          {{ member.name }}
          <button
            class="plain slim subdued"
            tabindex = "0"
            v-if = "member.new"
            @click = "removeMember(member.index)"
          >
            {{ $t('group.remove') }}
          </button>
        </li>
      </ul>
    </div>
    <div v-else-if = "isPresetGroups">
      {{ $t('group.needpreset') }}
    </div>
    <div v-if = "canAddMembers">
      <label for="addtogroup">
        {{ $t('group.add') }}
      </label>
      <select v-model = "newMember" id="addtogroup">
        <option value="0">{{ $t('group.select') }}</option>
        <option
          v-for = "user in availableUsers"
          :value = "user.id"
          :key = "user.id"
        >
          {{ user.name }}
        </option>
      </select>
      <button
        class="slim"
        @click = "addMember"
      >
        {{ $t('group.addbutton') }}
      </button>
    </div>
  </div>
</template>

<script>
import { store } from '../../basicstore';

export default {
  name: 'GroupEntry',
  data: function () {
    return {
      newMember: 0,
      newGroupMembers: [] // array of user IDs
    };
  },
  computed: {
    groupMax () {
      return store.assessInfo.groupmax;
    },
    groupMembers () {
      var out = [];
      if (!store.assessInfo.hasOwnProperty('group_members')) {
        return out;
      }
      for (let i = 0; i < store.assessInfo.group_members.length; i++) {
        out.push({
          name: store.assessInfo.group_members[i],
          new: false
        });
      }
      for (let i = 0; i < this.newGroupMembers.length; i++) {
        out.push({
          name: store.assessInfo.group_avail[this.newGroupMembers[i]],
          new: true,
          index: i
        });
      }
      return out;
    },
    availableUsers () {
      var out = [];
      for (const userid in store.assessInfo.group_avail) {
        if (this.newGroupMembers.indexOf(userid) === -1) {
          out.push({
            id: userid,
            name: store.assessInfo.group_avail[userid]
          });
        }
      }
      return out;
    },
    showMax () {
      return (store.assessInfo.isgroup === 2);
    },
    canAddMembers () {
      return (!this.canViewAll &&
        store.assessInfo.isgroup === 2 &&
        this.groupMembers.length < store.assessInfo.groupmax
      );
    },
    isPresetGroups () {
      return (store.assessInfo.isgroup === 3);
    },
    canViewAll () {
      return store.assessInfo.can_view_all;
    },
    teacherNote () {
      if (this.isPresetGroups) {
        return this.$t('group.teacher_preset');
      } else {
        return this.$t('group.teacher_auto', { n: store.assessInfo.groupmax });
      }
    }
  },
  methods: {
    removeMember (index) {
      this.newGroupMembers.splice(index, 1);
      this.handleChange();
    },
    addMember () {
      if (this.newMember > 0) {
        this.newGroupMembers.push(this.newMember);
        this.newMember = 0;
        this.handleChange();
      }
    },
    handleChange () {
      this.$emit('update-new-group', this.newGroupMembers);
    }
  }
};
</script>
