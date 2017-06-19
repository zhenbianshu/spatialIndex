#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <time.h>
#include "quadTree.h"

/**
 * 插入元素
 * 1.判断是否已分裂，已分裂的选择适合的子结点，插入；
 * 2.未分裂的查看是否过载，过载的分裂结点，重新插入；
 * 3.未过载的直接添加
 *
 * @param node
 * @param ele
 * todo 使用元素原地址，避免重新分配内存造成的效率浪费
 */
void insertEle(struct QuadTreeNode *node, struct ElePoint ele) {
    if (1 == node->is_leaf) {
        if (node->ele_num + 1 > MAX_ELE_NUM) {
            splitNode(node);
            insertEle(node, ele);
        } else {
            // todo 点排重（不排重的话如果相同的点数目大于 MAX_ELE_NUM， 会造成无限循环分裂）
            struct ElePoint *ele_ptr = (struct ElePoint *) malloc(sizeof(struct ElePoint));
            ele_ptr->lat = ele.lat;
            ele_ptr->lng = ele.lng;
            strcpy(ele_ptr->desc, ele.desc);
            node->ele_list[node->ele_num] = ele_ptr;
            node->ele_num++;
        }

        return;
    }


    double mid_vertical = (node->region.up + node->region.bottom) / 2;
    double mid_horizontal = (node->region.left + node->region.right) / 2;
    if (ele.lat > mid_vertical) {
        if (ele.lng > mid_horizontal) {
            insertEle(node->RU, ele);
        } else {
            insertEle(node->LU, ele);
        }
    } else {
        if (ele.lng > mid_horizontal) {
            insertEle(node->RB, ele);
        } else {
            insertEle(node->LB, ele);
        }
    }
}

/**
 * 分裂结点
 * 1.通过父结点获取子结点的深度和范围
 * 2.生成四个结点，挂载到父结点下
 *
 * @param node
 */
void splitNode(struct QuadTreeNode *node) {
    double mid_vertical = (node->region.up + node->region.bottom) / 2;
    double mid_horizontal = (node->region.left + node->region.right) / 2;

    node->is_leaf = 0;
    node->RU = createChildNode(node, mid_vertical, node->region.up, mid_horizontal, node->region.right);
    node->LU = createChildNode(node, mid_vertical, node->region.up, node->region.left, mid_horizontal);
    node->RB = createChildNode(node, node->region.bottom, mid_vertical, mid_horizontal, node->region.right);
    node->LB = createChildNode(node, node->region.bottom, mid_vertical, node->region.left, mid_horizontal);

    for (int i = 0; i < node->ele_num; i++) {
        insertEle(node, *node->ele_list[i]);
        free(node->ele_list[i]);
        node->ele_num--;
    }
}

struct QuadTreeNode *createChildNode(struct QuadTreeNode *node, double bottom, double up, double left, double right) {
    int depth = node->depth + 1;
    struct QuadTreeNode *childNode = (struct QuadTreeNode *) malloc(sizeof(struct QuadTreeNode));
    struct Region *region = (struct Region *) malloc(sizeof(struct Region));
    initRegion(region, bottom, up, left, right);
    initNode(childNode, depth, *region);

    return childNode;
}

void deleteEle(struct QuadTreeNode *node, struct ElePoint ele) {
    /**
     * 1.遍历元素列表，删除对应元素
     * 2.检查兄弟象限元素总数，不超过最大量时组合兄弟象限
     */
}

void combineNode(struct QuadTreeNode *node) {
    /**
     * 遍历四个子象限的点，添加到象限点列表
     * 释放子象限的内存
     */
}

void queryEle(struct QuadTreeNode node, struct ElePoint ele) {
    if (node.is_leaf == 1) {
        printf("附近点有%d个，分别是：\n", node.ele_num);
        for (int j = 0; j < node.ele_num; j++) {
            printf("%f,%f\n", node.ele_list[j]->lng, node.ele_list[j]->lat);
        }
        return;
    }

    double mid_vertical = (node.region.up + node.region.bottom) / 2;
    double mid_horizontal = (node.region.left + node.region.right) / 2;

    if (ele.lat > mid_vertical) {
        if (ele.lng > mid_horizontal) {
            queryEle(*node.RU, ele);
        } else {
            queryEle(*node.LU, ele);
        }
    } else {
        if (ele.lng > mid_horizontal) {
            queryEle(*node.RB, ele);
        } else {
            queryEle(*node.LB, ele);
        }
    }
}

void initNode(struct QuadTreeNode *node, int depth, struct Region region) {
    node->depth = depth;
    node->is_leaf = 1;
    node->ele_num = 0;
    node->region = region;
}

void initRegion(struct Region *region, double bottom, double up, double left, double right) {
    region->bottom = bottom;
    region->up = up;
    region->left = left;
    region->right = right;
}

int main() {
    struct QuadTreeNode root;
    struct Region root_region;

    struct ElePoint ele;
    initRegion(&root_region, -90, 90, -180, 180);
    initNode(&root, 1, root_region);

    srand((int)time(NULL));
    for (int i = 0; i < 100000; i++) {
        ele.lng = (float)(rand() % 360 - 180 + (float)(rand() % 1000) / 1000);
        ele.lat = (float)(rand() % 180 - 90 + (float)(rand() % 1000) / 1000);
        insertEle(&root, ele);
    }

    struct ElePoint test;
    test.lat = -24;
    test.lng = -45.4;
    queryEle(root, test);
}